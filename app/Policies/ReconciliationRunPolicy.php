<?php

namespace App\Policies;

use App\Models\ReconciliationRun;
use App\Models\User;

class ReconciliationRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reconciliation.view');
    }

    public function view(User $user, ReconciliationRun $run): bool
    {
        return $user->can('reconciliation.view')
            && ($run->branch_id === null ? $user->canAccessAllBranches() : $user->canAccessBranch($run->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->can('reconciliation.run');
    }

    public function close(User $user, ReconciliationRun $run): bool
    {
        return $user->can('reconciliation.close')
            && ($run->branch_id === null ? $user->canAccessAllBranches() : $user->canAccessBranch($run->branch_id));
    }
}
