<?php

namespace App\Models;

use App\Enums\VerificationDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionVerification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'collection_batch_id',
        'verified_by',
        'decision',
        'expected_total',
        'counted_total',
        'variance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'decision' => VerificationDecision::class,
            'expected_total' => 'decimal:2',
            'counted_total' => 'decimal:2',
            'variance' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CollectionBatch::class, 'collection_batch_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
