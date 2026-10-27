<?php

namespace App\Policies;

use App\Models\AccountTreatmentRequest;
use App\Models\User;

class AccountTreatmentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treatment.view');
    }

    public function view(User $user, AccountTreatmentRequest $request): bool
    {
        return $user->can('treatment.view') && $user->canAccessBranch($request->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('treatment.create');
    }

    public function review(User $user, AccountTreatmentRequest $request): bool
    {
        return $user->can('treatment.review') && $user->canAccessBranch($request->branch_id);
    }

    public function approve(User $user, AccountTreatmentRequest $request): bool
    {
        return $user->can('treatment.approve') && $user->canAccessBranch($request->branch_id);
    }

    public function complete(User $user, AccountTreatmentRequest $request): bool
    {
        return $user->can('treatment.complete') && $user->canAccessBranch($request->branch_id);
    }
}
