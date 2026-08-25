<?php

namespace App\Domain\Trip\Enums;

enum TripAssignmentStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Boarded = 'boarded';
    case NoShow = 'no_show';
}
