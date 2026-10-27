<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\LodgementStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollectionBatch extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;
    use HasWorkflow;

    protected $fillable = [
        'batch_number',
        'cashier_id',
        'branch_id',
        'collection_point_id',
        'cashier_shift_id',
        'business_date',
        'expected_total',
        'declared_total',
        'counted_total',
        'variance',
        'item_count',
        'submitted_at',
        'submitted_by',
        'verified_at',
        'verified_by',
        'approved_at',
        'approved_by',
        'returned_at',
        'return_reason',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'business_date' => 'date',
            'expected_total' => 'decimal:2',
            'declared_total' => 'decimal:2',
            'counted_total' => 'decimal:2',
            'variance' => 'decimal:2',
            'item_count' => 'integer',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'returned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollectionBatchItem::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'collection_batch_items')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CollectionVerification::class);
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(CollectionVerification::class)->latestOfMany();
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(CollectionDiscrepancy::class);
    }

    public function lodgementItems(): HasMany
    {
        return $this->hasMany(BankLodgementItem::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where('batch_number', 'like', '%'.trim($term).'%');
    }

    public function scopeForPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('business_date', [$from, $to]);
    }

    public function activeLodgementItems(): HasMany
    {
        return $this->lodgementItems()
            ->whereHas('lodgement', fn (Builder $q) => $q->whereNotIn('status', [
                LodgementStatus::Returned->value,
                LodgementStatus::Rejected->value,
            ]));
    }

    public function allocatedTotal(): string
    {
        return (string) $this->activeLodgementItems()->sum('amount');
    }

    public function outstandingAmount(): string
    {
        return number_format(
            max(0, (float) $this->expected_total - (float) $this->allocatedTotal()),
            2,
            '.',
            ''
        );
    }

    public function isFullyAllocated(): bool
    {
        return (float) $this->outstandingAmount() <= 0.0;
    }

    public function canBeEdited(): bool
    {
        return $this->status->isEditable();
    }
}
