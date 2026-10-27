<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountTreatmentType extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_approval',
        'approval_workflow_id',
        'required_document_types',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'required_document_types' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }

    public function reasons(): HasMany
    {
        return $this->hasMany(AccountTreatmentReason::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AccountTreatmentRequest::class);
    }
}
