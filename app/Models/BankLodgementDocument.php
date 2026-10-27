<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankLodgementDocument extends Model
{
    use Auditable;

    protected $fillable = [
        'bank_lodgement_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function lodgement(): BelongsTo
    {
        return $this->belongsTo(BankLodgement::class, 'bank_lodgement_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
