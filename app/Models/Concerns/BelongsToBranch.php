<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllBranches()) {
            return $query;
        }

        $branches = $user->accessibleBranchIds();

        return $branches === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn($this->qualifyColumn('branch_id'), $branches);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $branchId ? $query->where($this->qualifyColumn('branch_id'), $branchId) : $query;
    }
}
