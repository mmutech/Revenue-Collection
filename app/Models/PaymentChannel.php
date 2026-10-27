<?php

namespace App\Models;

use App\Enums\PaymentChannelCode;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentChannel extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'requires_receipt',
        'requires_external_reference',
        'requires_bank',
        'requires_bank_branch',
        'requires_terminal',
        'requires_cheque_details',
        'requires_settlement_date',
        'required_fields',
        'settings',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_receipt' => 'boolean',
            'requires_external_reference' => 'boolean',
            'requires_bank' => 'boolean',
            'requires_bank_branch' => 'boolean',
            'requires_terminal' => 'boolean',
            'requires_cheque_details' => 'boolean',
            'requires_settlement_date' => 'boolean',
            'required_fields' => 'array',
            'settings' => 'array',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'channel_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function isCash(): bool
    {
        return $this->code === PaymentChannelCode::Cash->value;
    }

    public function isCheque(): bool
    {
        return $this->code === PaymentChannelCode::Cheque->value;
    }

    public function channelCode(): ?PaymentChannelCode
    {
        return PaymentChannelCode::tryFrom($this->code);
    }
}
