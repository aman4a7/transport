<?php

namespace App\Console\Commands\Compliance;

use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Compliance\Services\ComplianceDocumentService;
use Illuminate\Console\Command;

class CheckExpirations extends Command
{
    protected $signature = 'compliance:check-expirations';

    protected $description = 'Mark approved compliance documents as expired where expiry date has passed';

    public function __construct(
        private readonly ComplianceDocumentService $complianceDocumentService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $documents = ComplianceDocument::query()
            ->where('status', ComplianceStatus::Approved)
            ->where('expires_at', '<', now())
            ->cursor();

        $count = 0;

        foreach ($documents as $document) {
            $this->complianceDocumentService->markExpired($document);
            $count++;
        }

        $this->info("Marked {$count} compliance documents as expired.");

        return Command::SUCCESS;
    }
}
