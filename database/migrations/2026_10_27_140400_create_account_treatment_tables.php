<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_treatment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->foreignId('approval_workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->json('required_document_types')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('account_treatment_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_treatment_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_treatment_type_id', 'code'], 'treatment_reason_type_code_uq');
        });

        Schema::create('account_treatment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_treatment_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_treatment_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approval_workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 19, 2)->nullable();
            $table->char('currency', 3)->default('NGN');
            $table->date('effective_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedSmallInteger('current_stage')->default(0);
            $table->text('description');
            $table->text('decision_notes')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_lodgement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reconciliation_run_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['account_id', 'status']);
        });

        Schema::create('account_treatment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_treatment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('stage_sequence');
            $table->string('stage_name', 120);
            $table->string('approver_role', 80)->nullable();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['account_treatment_request_id', 'stage_sequence'], 'treatment_approvals_request_stage_idx');
        });

        Schema::create('account_treatment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_treatment_request_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            $table->string('document_type', 60)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_treatment_documents');
        Schema::dropIfExists('account_treatment_approvals');
        Schema::dropIfExists('account_treatment_requests');
        Schema::dropIfExists('account_treatment_reasons');
        Schema::dropIfExists('account_treatment_types');
    }
};
