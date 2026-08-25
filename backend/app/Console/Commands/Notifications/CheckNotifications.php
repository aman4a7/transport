<?php

namespace App\Console\Commands\Notifications;

use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Contract\Enums\ContractStatus;
use App\Domain\Contract\Models\Contract;
use App\Domain\Fuel\Models\FuelStock;
use App\Domain\Garage\Enums\MaintenanceStatus;
use App\Domain\Garage\Models\MaintenanceRecord;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Services\NotificationService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CheckNotifications extends Command
{
    protected $signature = 'notifications:check';

    protected $description = 'Generate notifications for expiring compliance/contracts, due maintenance, and low fuel stock';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $complianceCount = $this->checkComplianceExpiring();
        $contractCount = $this->checkContractExpiring();
        $maintenanceCount = $this->checkMaintenanceDue();
        $fuelCount = $this->checkFuelLowStock();

        $total = $complianceCount + $contractCount + $maintenanceCount + $fuelCount;

        $this->info("Created {$total} notifications (compliance: {$complianceCount}, contracts: {$contractCount}, maintenance: {$maintenanceCount}, fuel: {$fuelCount}).");

        return Command::SUCCESS;
    }

    private function checkComplianceExpiring(): int
    {
        $recipients = $this->recipientsFor('compliance.view');
        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;

        $documents = ComplianceDocument::query()
            ->with('documentable')
            ->where('status', ComplianceStatus::Approved)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->cursor();

        foreach ($documents as $document) {
            $label = $this->labelFor($document->documentable);

            foreach ($recipients as $user) {
                if (! NotificationPreference::enabledFor($user->id, NotificationType::ComplianceExpiring)) {
                    continue;
                }

                $created = $this->notificationService->createUnique(
                    $user->id,
                    NotificationType::ComplianceExpiring,
                    "Compliance expiring: {$label}",
                    "The compliance document expires on {$document->expires_at->format('Y-m-d')}.",
                    [
                        'entity_type' => 'compliance_document',
                        'entity_id' => $document->id,
                        'documentable_type' => $document->documentable_type,
                        'documentable_id' => $document->documentable_id,
                        'expires_at' => $document->expires_at->toDateString(),
                    ],
                );

                if ($created) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function checkContractExpiring(): int
    {
        $recipients = $this->recipientsFor('contracts.view');
        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;

        $contracts = Contract::query()
            ->with(['vehicle:id,plate_number', 'owner:id,company_name'])
            ->where('status', ContractStatus::Active)
            ->where('end_date', '>', now())
            ->where('end_date', '<=', now()->addDays(30))
            ->cursor();

        foreach ($contracts as $contract) {
            foreach ($recipients as $user) {
                if (! NotificationPreference::enabledFor($user->id, NotificationType::ContractExpiring)) {
                    continue;
                }

                $created = $this->notificationService->createUnique(
                    $user->id,
                    NotificationType::ContractExpiring,
                    "Contract expiring: {$contract->contract_number}",
                    "The contract for {$contract->vehicle?->plate_number} expires on {$contract->end_date->format('Y-m-d')}.",
                    [
                        'entity_type' => 'contract',
                        'entity_id' => $contract->id,
                        'vehicle_id' => $contract->vehicle_id,
                        'expires_at' => $contract->end_date->toDateString(),
                    ],
                );

                if ($created) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function checkMaintenanceDue(): int
    {
        $recipients = $this->recipientsFor('garage.view');
        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;

        $records = MaintenanceRecord::query()
            ->with('vehicle:id,plate_number')
            ->where('status', MaintenanceStatus::Pending)
            ->where('scheduled_date', '>=', now()->startOfDay())
            ->where('scheduled_date', '<=', now()->addDays(7)->endOfDay())
            ->cursor();

        foreach ($records as $record) {
            foreach ($recipients as $user) {
                if (! NotificationPreference::enabledFor($user->id, NotificationType::MaintenanceDue)) {
                    continue;
                }

                $created = $this->notificationService->createUnique(
                    $user->id,
                    NotificationType::MaintenanceDue,
                    "Maintenance due: {$record->vehicle?->plate_number}",
                    "Scheduled maintenance is due on {$record->scheduled_date->format('Y-m-d')}.",
                    [
                        'entity_type' => 'maintenance',
                        'entity_id' => $record->id,
                        'vehicle_id' => $record->vehicle_id,
                        'scheduled_date' => $record->scheduled_date->toDateString(),
                    ],
                );

                if ($created) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function checkFuelLowStock(): int
    {
        $recipients = $this->recipientsFor('fuel.view_stock');
        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;

        $stocks = FuelStock::query()
            ->whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->cursor();

        foreach ($stocks as $stock) {
            foreach ($recipients as $user) {
                if (! NotificationPreference::enabledFor($user->id, NotificationType::FuelLowStock)) {
                    continue;
                }

                $created = $this->notificationService->createUnique(
                    $user->id,
                    NotificationType::FuelLowStock,
                    'Fuel stock low: '.strtoupper($stock->fuel_type),
                    "Current stock is {$stock->current_quantity} L against a minimum of {$stock->minimum_quantity} L.",
                    [
                        'entity_type' => 'fuel_stock',
                        'entity_id' => $stock->id,
                        'fuel_type' => $stock->fuel_type,
                        'current_quantity' => $stock->current_quantity,
                        'minimum_quantity' => $stock->minimum_quantity,
                    ],
                );

                if ($created) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function recipientsFor(string $permission): Collection
    {
        return User::whereHas('roles', function ($query) use ($permission): void {
            $query->where(function ($query): void {
                $query->whereNull('user_role.expires_at')
                    ->orWhere('user_role.expires_at', '>', now());
            })
                ->whereHas('permissions', function ($query) use ($permission): void {
                    $query->where('slug', $permission);
                });
        })->get(['id']);
    }

    private function labelFor(?Model $documentable): string
    {
        if ($documentable === null) {
            return 'entity';
        }

        $plate = $documentable->plate_number ?? null;
        if ($plate) {
            return $plate;
        }

        $license = $documentable->license_number ?? null;
        if ($license) {
            return $license;
        }

        $company = $documentable->company_name ?? null;
        if ($company) {
            return $company;
        }

        $name = $documentable->full_name ?? $documentable->name ?? null;

        return $name ?? class_basename($documentable).' #'.$documentable->getKey();
    }
}
