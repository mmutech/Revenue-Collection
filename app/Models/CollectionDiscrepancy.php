<?php

namespace App\Models;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionDiscrepancy extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;
    use HasWorkflow;

    protected $fillable = [
        'reference',
        'collection_batch_id',
        'payment_id',
        'branch_id',
        'bank_lodgement_id',
        'type',
        'amount',
        'description',
        'assigned_to',
        'resolution',
        'resolved_by',
        'resolved_at',
        'escalated_to',
        'escalated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscrepancyType::class,
            'status' => DiscrepancyStatus::class,
            'amount' => 'decimal:2',
            'resolved_at' => 'datetime',
            'escalated_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CollectionBatch::class, 'collection_batch_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function lodgement(): BelongsTo
    {
        return $this->belongsTo(BankLodgement::class, 'bank_lodgement_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function escalator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            DiscrepancyStatus::Resolved->value,
            DiscrepancyStatus::Closed->value,
        ]);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference', 'like', '%'.trim($term).'%')
                ->orWhere('description', 'like', '%'.trim($term).'%');
        });
    }
}
