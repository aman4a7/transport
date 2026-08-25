<?php

namespace App\Domain\Notification\Enums;

enum NotificationType: string
{
    case ComplianceExpiring = 'compliance_expiring';
    case ContractExpiring = 'contract_expiring';
    case MaintenanceDue = 'maintenance_due';
    case FuelLowStock = 'fuel_low_stock';

    public function label(): string
    {
        return match ($this) {
            self::ComplianceExpiring => 'Compliance expiring',
            self::ContractExpiring => 'Contract expiring',
            self::MaintenanceDue => 'Maintenance due',
            self::FuelLowStock => 'Fuel stock low',
        };
    }
}
