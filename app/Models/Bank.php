<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function bankBranches(): HasMany
    {
        return $this->hasMany(BankBranch::class);
    }

    public function lodgements(): HasMany
    {
        return $this->hasMany(BankLodgement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
