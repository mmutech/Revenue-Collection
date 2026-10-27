<?php

namespace App\Models;

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'type',
        'period_start',
        'period_end',
        'branch_id',
        'created_by',
        'started_at',
        'completed_at',
        'matched_count',
        'unmatched_count',
        'total_expected',
        'total_actual',
        'total_variance',
        'summary',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'matched_count' => 'integer',
            'unmatched_count' => 'integer',
            'total_expected' => 'decimal:2',
            'total_actual' => 'decimal:2',
            'total_variance' => 'decimal:2',
            'summary' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treatmentRequests(): HasMany
    {
        return $this->hasMany(AccountTreatmentRequest::class);
    }
}
