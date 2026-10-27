<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflowStage extends Model
{
    protected $fillable = [
        'approval_workflow_id',
        'sequence',
        'name',
        'approver_role',
        'permission',
        'required_approvals',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'required_approvals' => 'integer',
            'is_final' => 'boolean',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class);
    }

    public function treatmentApprovals(): HasMany
    {
        return $this->hasMany(AccountTreatmentApproval::class);
    }
}
