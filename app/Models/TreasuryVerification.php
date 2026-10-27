<?php

namespace App\Models;

use App\Enums\TreasuryDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryVerification extends Model
{
    protected $fillable = [
        'bank_lodgement_id',
        'officer_id',
        'decision',
        'approved_amount',
        'deposited_amount',
        'variance',
        'bank_confirmation_reference',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => TreasuryDecision::class,
            'approved_amount' => 'decimal:2',
            'deposited_amount' => 'decimal:2',
            'variance' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function lodgement(): BelongsTo
    {
        return $this->belongsTo(BankLodgement::class, 'bank_lodgement_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
