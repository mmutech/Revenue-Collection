<?php

namespace App\Models;

use App\Enums\AdjustmentStatus;
use App\Enums\AdjustmentType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAdjustment extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'reference',
        'payment_id',
        'type',
        'amount',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => AdjustmentType::class,
            'status' => AdjustmentStatus::class,
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
