<?php

namespace App\Domain\Owner\Enums;

enum OwnerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
}
