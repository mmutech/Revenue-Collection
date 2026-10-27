<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 150);
            $table->string('subject_type', 80);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name', 120);
            $table->string('approver_role', 80);
            $table->string('permission', 120)->nullable();
            $table->unsignedSmallInteger('required_approvals')->default(1);
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['approval_workflow_id', 'sequence']);
        });

        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('approval_workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('stage_sequence');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['approval_workflow_stage_id', 'decision']);
        });

        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at'], 'workflow_transitions_subject_idx');
            $table->index(['to_status', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 60);
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('tags', 180)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 80);
            $table->string('format', 10);
            $table->json('filters')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('approval_records');
        Schema::dropIfExists('approval_workflow_stages');
        Schema::dropIfExists('approval_workflows');
    }
};
