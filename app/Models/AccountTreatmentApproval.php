<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTreatmentApproval extends Model
{
    protected $fillable = [
        'account_treatment_request_id',
        'approval_workflow_stage_id',
        'stage_sequence',
        'stage_name',
        'approver_role',
        'approver_id',
        'decision',
        'comments',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AccountTreatmentRequest::class, 'account_treatment_request_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowStage::class, 'approval_workflow_stage_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
