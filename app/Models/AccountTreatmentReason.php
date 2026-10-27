<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountTreatmentReason extends Model
{
    use Auditable;

    protected $fillable = [
        'account_treatment_type_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccountTreatmentType::class, 'account_treatment_type_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AccountTreatmentRequest::class, 'account_treatment_reason_id');
    }
}
