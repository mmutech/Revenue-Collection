<?php

namespace App\Policies;

use App\Models\CashierShift;
use App\Models\User;

class CashierShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shifts.manage');
    }

    public function view(User $user, CashierShift $shift): bool
    {
        return $user->can('shifts.manage') && $user->canAccessBranch($shift->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('shifts.manage');
    }

    public function close(User $user, CashierShift $shift): bool
    {
        return $user->can('shifts.manage') && $user->canAccessBranch($shift->branch_id);
    }
}
