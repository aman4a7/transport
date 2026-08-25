<?php

namespace App\Domain\Fuel\Services;

use App\Domain\Fuel\Enums\FuelTransactionType;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Fuel\Models\FuelTransaction;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
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

        $sortField = SafeSort::field(
            ['created_at', 'fuel_type', 'quantity', 'transaction_type'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function getStocks(): iterable
    {
        return FuelStock::all();
    }

    public function getStock(string $fuelType): FuelStock
    {
        return FuelStock::where('fuel_type', $fuelType)->firstOrFail();
    }

    public function issueFuel(array $data): FuelTransaction
    {
        return DB::transaction(function () use ($data): FuelTransaction {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);

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

            if ($vehicle->fuel_type?->value !== $fuelType) {
                $vehicleFuelType = $vehicle->fuel_type?->value ?? 'unknown';

                throw new BusinessRuleException(
                    message: "Vehicle {$vehicle->plate_number} runs on {$vehicleFuelType} and cannot receive {$fuelType} fuel.",
                    rule: 'fuel_type_incompatible',
                );
            }

            $quantity = abs($data['quantity']);

            $stock = FuelStock::where('fuel_type', $fuelType)->lockForUpdate()->first();
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

            $this->auditLogService->log('fuel_issued', $transaction, null, null, [
                'vehicle_id' => $vehicle->id,
                'fuel_type' => $fuelType,
                'quantity' => $quantity,
            ]);

            return $transaction->fresh()->load(['vehicle:id,plate_number', 'issuer:id,name']);
        });
    }

    public function restock(array $data): FuelTransaction
    {
        return DB::transaction(function () use ($data): FuelTransaction {
            $fuelType = $data['fuel_type'];
            $quantity = abs($data['quantity']);

            $stock = FuelStock::firstOrCreate(
                ['fuel_type' => $fuelType],
                ['current_quantity' => 0, 'minimum_quantity' => 0, 'created_by' => Auth::id()],
            );

            $transaction = FuelTransaction::create([
                'vehicle_id' => null,
                'fuel_type' => $fuelType,
                'quantity' => $quantity,
                'transaction_type' => FuelTransactionType::Restock->value,
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => isset($data['unit_cost']) ? round($quantity * $data['unit_cost'], 2) : null,
                'issued_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $stock->increment('current_quantity', $quantity);
            $stock->update(['last_restocked_at' => now(), 'updated_by' => Auth::id()]);

            $this->auditLogService->log('fuel_restocked', $transaction, null, null, [
                'fuel_type' => $fuelType,
                'quantity' => $quantity,
            ]);

            return $transaction->fresh()->load(['issuer:id,name']);
        });
    }

    public function adjustStock(array $data): FuelTransaction
    {
        return DB::transaction(function () use ($data): FuelTransaction {
            $fuelType = $data['fuel_type'];
            $quantity = $data['quantity'];

            $stock = FuelStock::where('fuel_type', $fuelType)->lockForUpdate()->firstOrFail();

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

    public function getTransaction(int $id): FuelTransaction
    {
        return FuelTransaction::with(['vehicle:id,plate_number', 'issuer:id,name'])->findOrFail($id);
    }
}
