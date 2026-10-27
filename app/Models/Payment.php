<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Enums\ChequeStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;
    use HasWorkflow;

    protected $fillable = [
        'reference',
        'idempotency_key',
        'channel_id',
        'branch_id',
        'collection_point_id',
        'cashier_id',
        'shift_id',
        'customer_id',
        'payer_name',
        'payer_phone',
        'payer_email',
        'amount',
        'currency',
        'payment_date',
        'external_reference',
        'provider',
        'settlement_date',
        'bank_id',
        'bank_branch_id',
        'terminal_id',
        'cheque_number',
        'cheque_date',
        'cheque_drawer',
        'notes',
        'metadata',
        'verified_by',
        'verified_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'reversed_at',
        'reversal_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'cheque_status' => ChequeStatus::class,
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'settlement_date' => 'date',
            'cheque_date' => 'date',
            'verified_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class, 'channel_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankBranch(): BelongsTo
    {
        return $this->belongsTo(BankBranch::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }

    public function batchItem(): HasOne
    {
        return $this->hasOne(CollectionBatchItem::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PaymentAdjustment::class);
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(CollectionDiscrepancy::class);
    }

    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('reference', 'like', $like)
                ->orWhere('payer_name', 'like', $like)
                ->orWhere('external_reference', 'like', $like)
                ->orWhere('cheque_number', 'like', $like)
                ->orWhere('terminal_id', 'like', $like)
                ->orWhereHas('receipt', fn (Builder $r) => $r->where('receipt_number', 'like', $like));
        });
    }

    public function scopeForPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    public function isCheque(): bool
    {
        return $this->channel?->isCheque() ?? false;
    }

    public function isCash(): bool
    {
        return $this->channel?->isCash() ?? false;
    }

    public function requiresChequeClearance(): bool
    {
        return $this->isCheque()
            && $this->cheque_status !== ChequeStatus::Cleared;
    }

    public function isBatchable(): bool
    {
        return $this->status === PaymentStatus::Received
            && ! $this->requiresChequeClearance()
            && $this->batchItem === null;
    }

    public function isLockedInBatch(): bool
    {
        $batch = $this->batchItem?->batch;

        return $batch !== null && ! $batch->status->isEditable();
    }

    public function currentBatch(): ?CollectionBatch
    {
        return $this->batchItem?->batch;
    }

    public function isDeposited(): bool
    {
        $batch = $this->currentBatch();

        return $batch !== null && in_array($batch->status, [
            BatchStatus::FullyLodged,
            BatchStatus::TreasuryReview,
            BatchStatus::Reconciled,
            BatchStatus::Closed,
        ], true);
    }
}
