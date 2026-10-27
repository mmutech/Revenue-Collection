<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'key',
        'group',
        'label',
        'description',
        'type',
        'value',
        'is_public',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    /**
     * @return array<int, string>
     */
    public function auditExcluded(): array
    {
        return ['value', 'created_at', 'updated_at'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $this->value,
            'number' => (float) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
