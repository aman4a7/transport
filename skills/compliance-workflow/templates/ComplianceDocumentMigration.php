<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('type');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('file_size');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_documents');
    }
};
