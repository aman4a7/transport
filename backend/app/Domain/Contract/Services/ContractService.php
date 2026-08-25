<?php

namespace App\Domain\Contract\Services;

use App\Domain\Contract\Enums\ContractStatus;
use App\Domain\Contract\Models\Contract;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = Contract::query()->with(['vehicle:id,plate_number', 'owner:id,company_name']);

        if ($user !== null && $user->hasRole('contractor')) {
            $ownOwnerId = $user->owner?->id;

            if ($ownOwnerId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('owner_id', $ownOwnerId);
            }
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('end_date', '<=', $filters['date_to']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'contract_number', 'start_date', 'end_date', 'status'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data): Contract {
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);

            if ($vehicle->category->value !== 'contracted_private') {
                throw new BusinessRuleException(
                    message: 'Only contracted private vehicles can have contracts.',
                    rule: 'contract_vehicle_not_contracted',
                );
            }

            $this->assertOwnerOwnsVehicle($vehicle, (int) $data['owner_id']);
            $this->assertNoOverlappingActiveContract($vehicle->id, $data['start_date'], $data['end_date']);

            DB::select('select pg_advisory_xact_lock(?)', [crc32('contract-number:'.now()->year)]);

            $data['contract_number'] = 'CNT-'.now()->year.'-'.str_pad(
                (Contract::withTrashed()->max('id') ?? 0) + 1,
                4,
                '0',
                STR_PAD_LEFT,
            );
            $data['status'] = ContractStatus::Active->value;
            $data['created_by'] = Auth::id();

            $contract = Contract::create($data);

            $this->auditLogService->log('contract_created', $contract);

            return $contract->fresh()->load(['vehicle:id,plate_number', 'owner:id,company_name']);
        });
    }

    public function update(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data): Contract {
            $newVehicle = $contract->vehicle;
            if (isset($data['vehicle_id']) && $data['vehicle_id'] !== $contract->vehicle_id) {
                $newVehicle = Vehicle::findOrFail($data['vehicle_id']);
                if ($newVehicle->category->value !== 'contracted_private') {
                    throw new BusinessRuleException(
                        message: 'Only contracted private vehicles can have contracts.',
                        rule: 'contract_vehicle_not_contracted',
                    );
                }
            }

            $newOwnerId = isset($data['owner_id']) ? (int) $data['owner_id'] : (int) $contract->owner_id;
            $this->assertOwnerOwnsVehicle($newVehicle, $newOwnerId);

            $newStartDate = $data['start_date'] ?? $contract->start_date->format('Y-m-d');
            $newEndDate = $data['end_date'] ?? $contract->end_date->format('Y-m-d');
            if (isset($data['vehicle_id']) || isset($data['start_date']) || isset($data['end_date'])) {
                $this->assertNoOverlappingActiveContract($newVehicle->id, $newStartDate, $newEndDate, $contract->id);
            }

            $data['updated_by'] = Auth::id();
            $contract->update($data);

            $this->auditLogService->log('contract_updated', $contract);

            return $contract->fresh()->load(['vehicle:id,plate_number', 'owner:id,company_name']);
        });
    }

    public function delete(Contract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            if ($contract->status === ContractStatus::Active) {
                throw new BusinessRuleException(
                    message: 'Cannot delete an active contract. Terminate it first.',
                    rule: 'contract_cannot_delete_active',
                );
            }

            $contract->delete();

            $this->auditLogService->log('contract_deleted', $contract);
        });
    }

    public function getWithRelations(Contract $contract): Contract
    {
        return $contract->load(['vehicle', 'owner', 'createdBy', 'updatedBy']);
    }

    public function activate(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract): Contract {
            if ($contract->status === ContractStatus::Active) {
                throw new BusinessRuleException(
                    message: 'Contract is already active.',
                    rule: 'contract_already_active',
                );
            }

            $oldStatus = $contract->status->value;
            $this->assertNoOverlappingActiveContract(
                $contract->vehicle_id,
                $contract->start_date->format('Y-m-d'),
                $contract->end_date->format('Y-m-d'),
                $contract->id,
            );

            $contract->update([
                'status' => ContractStatus::Active->value,
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log(
                'contract_activated',
                $contract,
                null,
                ['status' => $oldStatus],
                ['status' => ContractStatus::Active->value],
            );

            return $contract->fresh()->load(['vehicle:id,plate_number', 'owner:id,company_name']);
        });
    }

    public function terminate(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract): Contract {
            if ($contract->status !== ContractStatus::Active) {
                throw new BusinessRuleException(
                    message: 'Only active contracts can be terminated.',
                    rule: 'contract_terminate_invalid_status',
                );
            }

            $oldStatus = $contract->status->value;
            $contract->update([
                'status' => ContractStatus::Terminated->value,
                'updated_by' => Auth::id(),
            ]);

            $this->auditLogService->log(
                'contract_terminated',
                $contract,
                null,
                ['status' => $oldStatus],
                ['status' => ContractStatus::Terminated->value],
            );

            return $contract->fresh()->load(['vehicle:id,plate_number', 'owner:id,company_name']);
        });
    }

    private function assertOwnerOwnsVehicle(Vehicle $vehicle, int $ownerId): void
    {
        if ((int) $vehicle->owner_id !== $ownerId) {
            throw new BusinessRuleException(
                message: 'The selected owner does not own the selected vehicle.',
                rule: 'contract_owner_vehicle_mismatch',
            );
        }
    }

    private function assertNoOverlappingActiveContract(int $vehicleId, string $startDate, string $endDate, ?int $excludeContractId = null): void
    {
        $query = Contract::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', ContractStatus::Active)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);

        if ($excludeContractId !== null) {
            $query->whereKeyNot($excludeContractId);
        }

        if ($query->exists()) {
            throw new BusinessRuleException(
                message: 'Vehicle already has an active contract overlapping these dates.',
                rule: 'contract_overlap_active',
            );
        }
    }
}
