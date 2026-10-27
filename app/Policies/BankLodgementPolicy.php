<?php

namespace App\Policies;

use App\Models\BankLodgement;
use App\Models\User;

class BankLodgementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lodgements.view');
    }

    public function view(User $user, BankLodgement $lodgement): bool
    {
        return $user->can('lodgements.view') && $user->canAccessBranch($lodgement->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('lodgements.create');
    }

    public function submit(User $user, BankLodgement $lodgement): bool
    {
        return $user->can('lodgements.submit') && $user->canAccessBranch($lodgement->branch_id);
    }

    public function return(User $user, BankLodgement $lodgement): bool
    {
        return $user->can('lodgements.return') && $user->canAccessBranch($lodgement->branch_id);
    }

    public function confirm(User $user, BankLodgement $lodgement): bool
    {
        return $user->can('treasury.confirm') && $user->canAccessBranch($lodgement->branch_id);
    }
}
