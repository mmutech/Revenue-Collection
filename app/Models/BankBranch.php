<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankBranch extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'code',
        'name',
        'sort_code',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function lodgements(): HasMany
    {
        return $this->hasMany(BankLodgement::class);
    }
}
