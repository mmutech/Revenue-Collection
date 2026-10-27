<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    use Auditable;
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'collection_point_id',
        'business_date',
        'opening_float',
        'expected_cash',
        'counted_cash',
        'variance',
        'status',
        'notes',
        'opened_at',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opening_float' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'status' => ShiftStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'shift_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(CollectionBatch::class, 'cashier_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }
}
