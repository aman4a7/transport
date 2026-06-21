<?php

namespace App\Domain\Vehicle\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case InMaintenance = 'in_maintenance';
    case Suspended = 'suspended';
    case Decommissioned = 'decommissioned';
}
