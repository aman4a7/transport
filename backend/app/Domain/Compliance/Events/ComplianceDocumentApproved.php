<?php

namespace App\Domain\Compliance\Events;

use App\Domain\Compliance\Models\ComplianceDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplianceDocumentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ComplianceDocument $document,
    ) {}
}
