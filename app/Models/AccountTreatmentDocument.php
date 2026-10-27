<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTreatmentDocument extends Model
{
    use Auditable;

    protected $fillable = [
        'account_treatment_request_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'document_type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AccountTreatmentRequest::class, 'account_treatment_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
