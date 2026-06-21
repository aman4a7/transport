<?php

namespace App\Domain\Compliance\Listeners;

use App\Domain\Compliance\Enums\ComplianceDocumentType;
use App\Domain\Compliance\Events\ComplianceDocumentApproved;
use App\Domain\Driver\Models\Driver;
use App\Domain\Vehicle\Models\Vehicle;

class SyncEntityExpiryDate
{
    public function handle(ComplianceDocumentApproved $event): void
    {
        $document = $event->document;
        $entity = $document->documentable;

        match (true) {
            $entity instanceof Vehicle => match ($document->type) {
                ComplianceDocumentType::VehicleRegistration => $entity->update([
                    'registration_expiry' => $document->expires_at,
                ]),
                ComplianceDocumentType::Insurance => $entity->update([
                    'insurance_expiry' => $document->expires_at,
                ]),
                default => null,
            },
            $entity instanceof Driver && $document->type === ComplianceDocumentType::DriverLicense => $entity->update([
                'license_expiry' => $document->expires_at,
            ]),
            default => null,
        };
    }
}
