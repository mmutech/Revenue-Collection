<?php

namespace App\Models;

use App\Enums\TreatmentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountTreatmentRequest extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;
    use HasWorkflow;

    protected $fillable = [
        'reference',
        'account_id',
        'branch_id',
        'account_treatment_type_id',
        'account_treatment_reason_id',
        'approval_workflow_id',
        'requested_by',
        'assigned_to',
        'amount',
        'currency',
        'effective_date',
        'current_stage',
        'description',
        'decision_notes',
        'payment_id',
        'collection_batch_id',
        'bank_lodgement_id',
        'reconciliation_run_id',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'completed_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentStatus::class,
            'amount' => 'decimal:2',
            'effective_date' => 'date',
            'current_stage' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccountTreatmentType::class, 'account_treatment_type_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(AccountTreatmentReason::class, 'account_treatment_reason_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function collectionBatch(): BelongsTo
    {
        return $this->belongsTo(CollectionBatch::class);
    }

    public function lodgement(): BelongsTo
    {
        return $this->belongsTo(BankLodgement::class, 'bank_lodgement_id');
    }

    public function reconciliationRun(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AccountTreatmentApproval::class)->orderBy('stage_sequence')->orderBy('decided_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AccountTreatmentDocument::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%'.trim($term).'%';
            $q->where('reference', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('account', fn (Builder $a) => $a->where('account_number', 'like', $like)->orWhere('name', 'like', $like));
        });
    }

    public function currentWorkflowStage(): ?ApprovalWorkflowStage
    {
        if (! $this->workflow) {
            return null;
        }

        return $this->workflow->stages()
            ->where('sequence', $this->current_stage)
            ->first();
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === TreatmentStatus::UnderReview;
    }
}
