<?php

namespace App\Domain\Driver\Enums;

enum DriverStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Inactive = 'inactive';
}
