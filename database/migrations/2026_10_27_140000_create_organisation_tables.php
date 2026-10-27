<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('collection_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('type', 30)->default('cashier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('short_name', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('sort_code', 20)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['bank_id', 'code']);
        });

        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 80);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_receipt')->default(false);
            $table->boolean('requires_external_reference')->default(false);
            $table->boolean('requires_bank')->default(false);
            $table->boolean('requires_bank_branch')->default(false);
            $table->boolean('requires_terminal')->default(false);
            $table->boolean('requires_cheque_details')->default(false);
            $table->boolean('requires_settlement_date')->default(false);
            $table->json('required_fields')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('group', 50)->default('general');
            $table->string('label', 150);
            $table->string('description')->nullable();
            $table->string('type', 20)->default('string');
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('name', 180);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('type', 20)->default('individual');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('type', 30)->default('customer');
            $table->string('status', 20)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'branch_id']);
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('prefix', 30);
            $table->string('period', 12)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('collection_point_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->string('employee_no', 30)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('job_title', 100)->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('branch_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('collection_point_id');
            $table->dropUnique(['employee_no']);
            $table->dropColumn(['employee_no', 'phone', 'job_title', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('payment_channels');
        Schema::dropIfExists('bank_branches');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('collection_points');
        Schema::dropIfExists('branches');
    }
};
