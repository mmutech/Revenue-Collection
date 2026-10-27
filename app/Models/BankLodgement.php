<?php

namespace App\Models;

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

class BankLodgement extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;
    use HasWorkflow;

    protected $fillable = [
        'lodgement_number',
        'bank_id',
        'bank_branch_id',
        'branch_id',
        'deposit_slip_number',
        'deposit_date',
        'amount',
        'currency',
        'deposited_by_name',
        'deposited_by_user_id',
        'created_by',
        'submitted_at',
        'submitted_by',
        'treasury_verified_by',
        'treasury_verified_at',
        'bank_confirmation_reference',
        'notes',
        'decision_notes',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LodgementStatus::class,
            'deposit_date' => 'date',
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'treasury_verified_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankBranch(): BelongsTo
    {
        return $this->belongsTo(BankBranch::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankLodgementItem::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(CollectionBatch::class, 'bank_lodgement_items')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BankLodgementDocument::class);
    }

    public function treasuryVerifications(): HasMany
    {
        return $this->hasMany(TreasuryVerification::class);
    }

    public function latestTreasuryVerification(): HasOne
    {
        return $this->hasOne(TreasuryVerification::class)->latestOfMany();
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(CollectionDiscrepancy::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function treasuryVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasury_verified_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%'.trim($term).'%';
            $q->where('lodgement_number', 'like', $like)
                ->orWhere('deposit_slip_number', 'like', $like)
                ->orWhere('bank_confirmation_reference', 'like', $like);
        });
    }

    public function scopeForPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('deposit_date', [$from, $to]);
    }

    public function allocatedTotal(): string
    {
        return (string) $this->items()->sum('amount');
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [LodgementStatus::Draft, LodgementStatus::Returned], true);
    }

    public function isConfirmed(): bool
    {
        return in_array($this->status, [LodgementStatus::Verified, LodgementStatus::Reconciled, LodgementStatus::Closed], true);
    }
}
