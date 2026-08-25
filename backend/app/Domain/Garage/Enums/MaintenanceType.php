<?php

namespace App\Domain\Garage\Enums;

enum MaintenanceType: string
{
    case Scheduled = 'scheduled';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Other = 'other';
}
