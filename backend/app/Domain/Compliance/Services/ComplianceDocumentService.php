<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Compliance\Enums\ComplianceStatus;
use App\Domain\Compliance\Events\ComplianceDocumentApproved;
use App\Domain\Compliance\Models\ComplianceDocument;
use App\Domain\Shared\Exceptions\BusinessRuleException;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComplianceDocumentService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = ComplianceDocument::query()->with(['documentable', 'submittedBy', 'reviewedBy']);

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

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function upload(array $data, UploadedFile $file, User $submittedBy): ComplianceDocument
    {
        return DB::transaction(function () use ($data, $file, $submittedBy): ComplianceDocument {
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
            Storage::disk('compliance')->delete($document->file_path);
            $document->delete();
        });
    }
}
