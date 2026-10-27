<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankLodgementItem extends Model
{
    protected $fillable = [
        'bank_lodgement_id',
        'collection_batch_id',
        'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
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
