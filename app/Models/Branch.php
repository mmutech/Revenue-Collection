<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function collectionPoints(): HasMany
    {
        return $this->hasMany(CollectionPoint::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(CollectionBatch::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
