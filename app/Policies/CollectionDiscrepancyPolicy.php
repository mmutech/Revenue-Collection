<?php

namespace App\Policies;

use App\Models\CollectionDiscrepancy;
use App\Models\User;

class CollectionDiscrepancyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('discrepancies.view');
    }

    public function view(User $user, CollectionDiscrepancy $discrepancy): bool
    {
        return $user->can('discrepancies.view') && $user->canAccessBranch($discrepancy->branch_id);
    }

    public function manage(User $user, CollectionDiscrepancy $discrepancy): bool
    {
        return $user->can('discrepancies.manage') && $user->canAccessBranch($discrepancy->branch_id);
    }

    public function escalate(User $user, CollectionDiscrepancy $discrepancy): bool
    {
        return $user->can('discrepancies.escalate') && $user->canAccessBranch($discrepancy->branch_id);
    }
}
