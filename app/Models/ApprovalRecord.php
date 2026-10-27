<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'approval_workflow_id',
        'subject_type',
        'subject_id',
        'approval_workflow_stage_id',
        'stage_sequence',
        'actor_id',
        'decision',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'created_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowStage::class, 'approval_workflow_stage_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
