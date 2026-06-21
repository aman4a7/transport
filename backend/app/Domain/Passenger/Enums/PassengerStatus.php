<?php

namespace App\Domain\Passenger\Enums;

enum PassengerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
