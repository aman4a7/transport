<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Events\ComplianceDocumentApproved;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Driver\Models\Driver;
use App\Domain\Owner\Models\Owner;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Support\SafeSort;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComplianceDocumentService
{
    private const TYPE_ENTITY_MAP = [
        'vehicle_registration' => [Vehicle::class],
        'insurance' => [Vehicle::class],
        'driver_license' => [Driver::class],
        'contract_document' => [Owner::class],
        'other' => [Vehicle::class, Driver::class, Owner::class],
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = ComplianceDocument::query()->with(['documentable', 'submittedBy', 'reviewedBy']);

        $this->applyListScope($query, $user);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['documentable_type'])) {
            $query->where('documentable_type', $filters['documentable_type'])
                ->where('documentable_id', $filters['documentable_id']);
        }

        $sortField = SafeSort::field(
            ['created_at', 'type', 'status', 'expires_at'],
            $filters['sort_by'] ?? null,
            'created_at',
        );
        $sortOrder = SafeSort::direction($filters['sort_dir'] ?? null);
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    private function applyListScope(Builder $query, ?User $user): void
    {
        if ($user === null) {
            return;
        }

        if ($user->hasRole('driver')) {
            $ownDriverId = $user->driver?->id;

            if ($ownDriverId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('documentable_type', Driver::class)->where('documentable_id', $ownDriverId);

            return;
        }

        if (! $user->hasRole('contractor')) {
            return;
        }

        $ownOwnerId = $user->owner?->id;

        if ($ownOwnerId === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $ownedVehicleIds = Vehicle::where('owner_id', $ownOwnerId)->pluck('id');

        $query->where(function (Builder $q) use ($ownOwnerId, $ownedVehicleIds): void {
            $q->where(function (Builder $owner) use ($ownOwnerId): void {
                $owner->where('documentable_type', Owner::class)->where('documentable_id', $ownOwnerId);
            })->orWhere(function (Builder $vehicle) use ($ownedVehicleIds): void {
                $vehicle->where('documentable_type', Vehicle::class)->whereIn('documentable_id', $ownedVehicleIds);
            });
        });
    }

    private function assertUploadAllowed(array $data, User $user): void
    {
        $documentableType = $data['documentable_type'];
        $type = $data['type'];

        $allowedEntities = self::TYPE_ENTITY_MAP[$type] ?? [];

        if (! in_array($documentableType, $allowedEntities, true)) {
            throw new BusinessRuleException(
                message: "Compliance document type '{$type}' cannot be attached to ".class_basename($documentableType).'.',
                rule: 'compliance_document_type_entity_mismatch',
            );
        }

        if ($user->hasRole('driver')) {
            $ownDriverId = $user->driver?->id;

            if ($ownDriverId === null || $documentableType !== Driver::class || (int) $data['documentable_id'] !== (int) $ownDriverId) {
                throw new BusinessRuleException(
                    message: 'Drivers can only upload compliance documents for their own driver record.',
                    rule: 'compliance_document_ownership_forbidden',
                );
            }

            return;
        }

        if (! $user->hasRole('contractor')) {
            return;
        }

        $ownOwnerId = $user->owner?->id;

        if ($ownOwnerId === null) {
            throw new BusinessRuleException(
                message: 'Contractors can only upload compliance documents for their own company or their own vehicles.',
                rule: 'compliance_document_ownership_forbidden',
            );
        }

        if ($documentableType === Owner::class) {
            if ((int) $data['documentable_id'] !== (int) $ownOwnerId) {
                throw new BusinessRuleException(
                    message: 'Contractors can only upload compliance documents for their own company.',
                    rule: 'compliance_document_ownership_forbidden',
                );
            }

            return;
        }

        if ($documentableType === Vehicle::class) {
            $ownsVehicle = Vehicle::where('owner_id', $ownOwnerId)->whereKey((int) $data['documentable_id'])->exists();

            if (! $ownsVehicle) {
                throw new BusinessRuleException(
                    message: 'Contractors can only upload compliance documents for vehicles they own.',
                    rule: 'compliance_document_ownership_forbidden',
                );
            }

            return;
        }

        throw new BusinessRuleException(
            message: 'Contractors can only upload compliance documents for their own company or their own vehicles.',
            rule: 'compliance_document_ownership_forbidden',
        );
    }

    public function upload(array $data, UploadedFile $file, User $submittedBy): ComplianceDocument
    {
        return DB::transaction(function () use ($data, $file, $submittedBy): ComplianceDocument {
            $this->assertUploadAllowed($data, $submittedBy);

            $path = $file->store('documents', 'compliance');

            $document = ComplianceDocument::create([
                'documentable_type' => $data['documentable_type'],
                'documentable_id' => $data['documentable_id'],
                'type' => $data['type'],
                'status' => ComplianceStatus::Pending,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'issued_at' => $data['issued_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'submitted_by' => $submittedBy->id,
            ]);

            $this->auditLogService->log(
                action: 'compliance_document.uploaded',
                subject: $document,
                actor: $submittedBy,
                newValues: ['type' => $data['type'], 'file' => $file->getClientOriginalName()],
                description: "Compliance document {$data['type']} uploaded for ".class_basename($data['documentable_type'])." #{$data['documentable_id']}",
            );

            return $document->load(['documentable', 'submittedBy']);
        });
    }

    public function approve(ComplianceDocument $document, User $reviewer): ComplianceDocument
    {
        return DB::transaction(function () use ($document, $reviewer): ComplianceDocument {
            if ($document->status !== ComplianceStatus::Pending) {
                throw new BusinessRuleException(
                    message: 'Only pending documents can be approved.',
                    rule: 'compliance_document_not_pending',
                );
            }

            $document->update([
                'status' => ComplianceStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->auditLogService->log(
                action: 'compliance_document.approved',
                subject: $document,
                actor: $reviewer,
                oldValues: ['status' => ComplianceStatus::Pending->value],
                newValues: ['status' => ComplianceStatus::Approved->value],
                description: "Compliance document #{$document->id} approved by {$reviewer->name}",
            );

            event(new ComplianceDocumentApproved($document));

            return $document->fresh(['documentable', 'submittedBy', 'reviewedBy']);
        });
    }

    public function reject(ComplianceDocument $document, User $reviewer, string $reason): ComplianceDocument
    {
        return DB::transaction(function () use ($document, $reviewer, $reason): ComplianceDocument {
            if ($document->status !== ComplianceStatus::Pending) {
                throw new BusinessRuleException(
                    message: 'Only pending documents can be rejected.',
                    rule: 'compliance_document_not_pending',
                );
            }

            if (empty(trim($reason))) {
                throw new BusinessRuleException(
                    message: 'Rejection reason is required.',
                    rule: 'compliance_rejection_reason_required',
                );
            }

            $document->update([
                'status' => ComplianceStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'compliance_document.rejected',
                subject: $document,
                actor: $reviewer,
                oldValues: ['status' => ComplianceStatus::Pending->value],
                newValues: ['status' => ComplianceStatus::Rejected->value, 'reason' => $reason],
                description: "Compliance document #{$document->id} rejected by {$reviewer->name}: {$reason}",
            );

            return $document->fresh(['documentable', 'submittedBy', 'reviewedBy']);
        });
    }

    public function markExpired(ComplianceDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            if ($document->status !== ComplianceStatus::Approved) {
                return;
            }

            $document->update(['status' => ComplianceStatus::Expired]);

            $this->auditLogService->log(
                action: 'compliance_document.expired',
                subject: $document,
                newValues: ['status' => ComplianceStatus::Expired->value],
                description: "Compliance document #{$document->id} expired",
            );
        });
    }

    public function delete(ComplianceDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            if ($document->status === ComplianceStatus::Approved) {
                throw new BusinessRuleException(
                    message: 'Cannot delete an approved compliance document.',
                    rule: 'compliance_cannot_delete_approved',
                );
            }

            Storage::disk('compliance')->delete($document->file_path);
            $document->delete();

            $this->auditLogService->log('compliance_document.deleted', $document);
        });
    }
}
