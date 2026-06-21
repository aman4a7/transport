<?php

namespace App\Domain\Vehicle\Enums;

enum FuelType: string
{
    case Diesel = 'diesel';
    case Petrol = 'petrol';
    case Electric = 'electric';
    case Hybrid = 'hybrid';
}
