<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'collection_point_id',
        'employee_no',
        'phone',
        'job_title',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionPoint(): BelongsTo
    {
        return $this->belongsTo(CollectionPoint::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'cashier_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(CollectionBatch::class, 'cashier_id');
    }

    public function openShift(): ?CashierShift
    {
        return $this->shifts()
            ->where('status', ShiftStatus::Open->value)
            ->latest('opened_at')
            ->first();
    }

    public function canAccessAllBranches(): bool
    {
        return $this->can('access_all_branches');
    }

    /**
     * @return array<int, int>
     */
    public function accessibleBranchIds(): array
    {
        $ids = Collection::make([$this->branch_id])
            ->merge($this->relationLoaded('branches')
                ? $this->branches->pluck('id')
                : $this->branches()->pluck('branches.id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids;
    }

    public function canAccessBranch(?int $branchId): bool
    {
        if (! $branchId) {
            return false;
        }

        return $this->canAccessAllBranches()
            || in_array((int) $branchId, $this->accessibleBranchIds(), true);
    }

    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function initials(): string
    {
        return Collection::make(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function isCashier(): bool
    {
        return $this->hasRole('Cashier');
    }
}
