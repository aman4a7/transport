<?php

namespace App\Domain\Contract\Enums;

enum ContractStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';
}
