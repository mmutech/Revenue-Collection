<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('collection_point_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->decimal('opening_float', 19, 2)->default(0);
            $table->decimal('expected_cash', 19, 2)->nullable();
            $table->decimal('counted_cash', 19, 2)->nullable();
            $table->decimal('variance', 19, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unsignedTinyInteger('open_slot')->nullable()->storedAs("case when status = 'open' then 1 else null end");
            $table->unique(['user_id', 'open_slot']);
            $table->index(['user_id', 'status']);
            $table->index(['branch_id', 'business_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('channel_id')->constrained('payment_channels')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('collection_point_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();

            $table->string('payer_name', 180);
            $table->string('payer_phone', 30)->nullable();
            $table->string('payer_email')->nullable();
            $table->decimal('amount', 19, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 30)->default('pending');
            $table->date('payment_date');
            $table->string('external_reference', 120)->nullable();
            $table->string('provider', 100)->nullable();
            $table->date('settlement_date')->nullable();
            $table->foreignId('bank_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('bank_branch_id')->nullable()->constrained('bank_branches')->restrictOnDelete();
            $table->string('terminal_id', 60)->nullable();
            $table->string('cheque_number', 40)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_drawer', 180)->nullable();
            $table->string('cheque_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'payment_date']);
            $table->index(['channel_id', 'payment_date']);
            $table->index(['cashier_id', 'payment_date']);
            $table->index(['status', 'payment_date']);
            $table->index('external_reference');
            $table->index('cheque_number');
            $table->index('settlement_date');
        });

        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 50)->unique();
            $table->string('status', 20)->default('issued');
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('reprint_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 19, 2);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
        });

        Schema::create('collection_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('collection_point_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->restrictOnDelete();
            $table->date('business_date');
            $table->string('status', 40)->default('draft');
            $table->decimal('expected_total', 19, 2)->default(0);
            $table->decimal('declared_total', 19, 2)->nullable();
            $table->decimal('counted_total', 19, 2)->nullable();
            $table->decimal('variance', 19, 2)->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->text('return_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'business_date']);
            $table->index(['cashier_id', 'business_date']);
            $table->index(['status', 'business_date']);
            $table->unique(['cashier_id', 'business_date'], 'batch_cashier_date_unique');
        });

        Schema::create('collection_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount', 19, 2);
            $table->timestamps();

            $table->index('collection_batch_id');
        });

        Schema::create('collection_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20);
            $table->decimal('expected_total', 19, 2);
            $table->decimal('counted_total', 19, 2)->nullable();
            $table->decimal('variance', 19, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['collection_batch_id', 'created_at']);
        });

        Schema::create('collection_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('collection_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('bank_lodgement_id')->nullable()->index();
            $table->string('type', 40);
            $table->decimal('amount', 19, 2)->default(0);
            $table->text('description');
            $table->string('status', 30)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['branch_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_discrepancies');
        Schema::dropIfExists('collection_verifications');
        Schema::dropIfExists('collection_batch_items');
        Schema::dropIfExists('collection_batches');
        Schema::dropIfExists('payment_adjustments');
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('cashier_shifts');
    }
};
