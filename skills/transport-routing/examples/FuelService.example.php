<?php

// EXAMPLE — modeled VERBATIM on the real app/Domain/Fuel/Services/FuelService.php.
// Demonstrates the full transport-routing pattern: eligibility-gate chain reading
// entity fields synced by the compliance listener, BusinessRuleException rule slugs,
// DB::transaction()-wrapped multi-step writes, log() vs logSensitive() severity,
// and column-scoped eager loading.

namespace App\Domain\Fuel\Services;

use App\Domain\Fuel\Enums\FuelTransactionType;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function listTransactions(array $filters = []): LengthAwarePaginator
    {
        $query = FuelTransaction::query()->with(['vehicle:id,plate_number', 'issuer:id,name']);

        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function issueFuel(array $data): FuelTransaction
    {
        // DB::transaction(): validation + transaction row + stock decrement + audit
        // all-or-nothing. Any BusinessRuleException rolls back every write.
        return DB::transaction(function () use ($data): FuelTransaction {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);

            // ── Eligibility gate chain (reads entity columns maintained by the
            //    compliance SyncEntityExpiryDate listener) ──
            if ($vehicle->category->value !== 'defence_plated') {
                throw new BusinessRuleException(
                    message: 'Only defence-plated vehicles can use fuel services.',
                    rule: 'fuel_vehicle_not_defence_plated',
                );
            }

            if ($vehicle->status->value !== 'active') {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} is not operational.",
                    rule: 'fuel_vehicle_not_operational',
                );
            }

            if ($vehicle->registration_expiry && $vehicle->registration_expiry->isPast()) {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} registration has expired.",
                    rule: 'fuel_vehicle_registration_expired',
                );
            }

            if ($vehicle->insurance_expiry && $vehicle->insurance_expiry->isPast()) {
                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} insurance has expired.",
                    rule: 'fuel_vehicle_insurance_expired',
                );
            }

            $fuelType = $data['fuel_type'];
            $quantity = abs($data['quantity']);

            $stock = FuelStock::where('fuel_type', $fuelType)->first();
            if (! $stock || $stock->current_quantity < $quantity) {
                throw new BusinessRuleException(
                    message: "Insufficient {$fuelType} stock.",
                    rule: 'fuel_insufficient_stock',
                );
            }

            $transaction = FuelTransaction::create([
                'vehicle_id' => $vehicle->id,
                'fuel_type' => $fuelType,
                'quantity' => -$quantity,
                'transaction_type' => FuelTransactionType::Issue->value,
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => isset($data['unit_cost']) ? round($quantity * $data['unit_cost'], 2) : null,
                'issued_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->decrement('current_quantity', $quantity);

            // log() — not logSensitive() — because the immutable transaction row
            // IS the authoritative audit record for an issue.
            $this->auditLogService->log('fuel_issued', $transaction, null, null, [
                'vehicle_id' => $vehicle->id,
                'fuel_type' => $fuelType,
                'quantity' => $quantity,
            ]);

            return $transaction->fresh()->load(['vehicle:id,plate_number', 'issuer:id,name']);
        });
    }

    public function adjustStock(array $data): FuelTransaction
    {
        return DB::transaction(function () use ($data): FuelTransaction {
            $fuelType = $data['fuel_type'];
            $quantity = $data['quantity'];

            $stock = FuelStock::where('fuel_type', $fuelType)->firstOrFail();

            $newQuantity = $stock->current_quantity + $quantity;
            if ($newQuantity < 0) {
                throw new BusinessRuleException(
                    message: "Adjustment would result in negative {$fuelType} stock.",
                    rule: 'fuel_adjustment_negative',
                );
            }

            $transaction = FuelTransaction::create([
                'vehicle_id' => null,
                'fuel_type' => $fuelType,
                'quantity' => $quantity,
                'transaction_type' => FuelTransactionType::Adjustment->value,
                'issued_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->update(['current_quantity' => $newQuantity, 'updated_by' => Auth::id()]);

            // logSensitive() — a manual balance-changing action, emitted as a
            // warning-level log in addition to the audit row.
            $this->auditLogService->logSensitive('fuel_stock_adjusted', $transaction, null, [
                'fuel_type' => $fuelType,
                'previous_quantity' => $stock->getOriginal('current_quantity'),
            ], [
                'fuel_type' => $fuelType,
                'new_quantity' => $newQuantity,
                'adjustment' => $quantity,
            ]);

            return $transaction->fresh()->load(['issuer:id,name']);
        });
    }
}