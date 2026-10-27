<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'subject_type',
        'description',
        'is_active',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'conditions' => 'array',
        ];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStage::class)->orderBy('sequence');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class);
    }

    public function treatmentTypes(): HasMany
    {
        return $this->hasMany(AccountTreatmentType::class);
    }
}
