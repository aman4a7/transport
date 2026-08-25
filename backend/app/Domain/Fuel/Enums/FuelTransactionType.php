<?php

namespace App\Domain\Fuel\Enums;

enum FuelTransactionType: string
{
    case Issue = 'issue';
    case Restock = 'restock';
    case Adjustment = 'adjustment';
}
