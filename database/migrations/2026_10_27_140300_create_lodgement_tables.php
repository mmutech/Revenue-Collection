<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_lodgements', function (Blueprint $table) {
            $table->id();
            $table->string('lodgement_number', 50)->unique();
            $table->foreignId('bank_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('deposit_slip_number', 60);
            $table->date('deposit_date');
            $table->decimal('amount', 19, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('deposited_by_name', 180);
            $table->foreignId('deposited_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treasury_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('treasury_verified_at')->nullable();
            $table->string('bank_confirmation_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->unique(['bank_id', 'deposit_slip_number']);
            $table->index(['status', 'deposit_date']);
            $table->index('deposit_date');
            $table->index(['branch_id', 'deposit_date']);
        });

        Schema::create('bank_lodgement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_lodgement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_batch_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 19, 2);
            $table->timestamps();

            $table->unique(['bank_lodgement_id', 'collection_batch_id'], 'lodgement_batch_unique');
            $table->index('collection_batch_id');
        });

        Schema::create('bank_lodgement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_lodgement_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('treasury_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_lodgement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20);
            $table->decimal('approved_amount', 19, 2);
            $table->decimal('deposited_amount', 19, 2);
            $table->decimal('variance', 19, 2)->default(0);
            $table->string('bank_confirmation_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['bank_lodgement_id', 'created_at']);
        });

        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('type', 40)->default('collections_vs_lodgements');
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->decimal('total_expected', 19, 2)->default(0);
            $table->decimal('total_actual', 19, 2)->default(0);
            $table->decimal('total_variance', 19, 2)->default(0);
            $table->json('summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['period_start', 'period_end']);
            $table->index('status');
        });

        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_lodgement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('expected_amount', 19, 2)->default(0);
            $table->decimal('actual_amount', 19, 2)->default(0);
            $table->decimal('variance', 19, 2)->default(0);
            $table->string('status', 20)->default('unmatched');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reconciliation_run_id', 'status'], 'recon_items_run_status_idx');
            $table->index('payment_id');
            $table->index('bank_lodgement_id');
        });

        Schema::table('collection_discrepancies', function (Blueprint $table) {
            $table->foreign('bank_lodgement_id')->references('id')->on('bank_lodgements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('collection_discrepancies', function (Blueprint $table) {
            $table->dropForeign(['bank_lodgement_id']);
        });
        Schema::dropIfExists('reconciliation_items');
        Schema::dropIfExists('reconciliation_runs');
        Schema::dropIfExists('treasury_verifications');
        Schema::dropIfExists('bank_lodgement_documents');
        Schema::dropIfExists('bank_lodgement_items');
        Schema::dropIfExists('bank_lodgements');
    }
};
