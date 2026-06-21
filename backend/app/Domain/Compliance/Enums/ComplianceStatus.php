<?php

namespace App\Domain\Compliance\Enums;

enum ComplianceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
}
