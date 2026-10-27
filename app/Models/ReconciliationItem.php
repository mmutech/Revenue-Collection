<?php

namespace App\Models;

use App\Enums\ReconciliationItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reconciliation_run_id',
        'type',
        'payment_id',
        'bank_lodgement_id',
        'collection_batch_id',
        'expected_amount',
        'actual_amount',
        'variance',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReconciliationItemStatus::class,
            'expected_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'variance' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class, 'reconciliation_run_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function lodgement(): BelongsTo
    {
        return $this->belongsTo(BankLodgement::class, 'bank_lodgement_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CollectionBatch::class, 'collection_batch_id');
    }
}
