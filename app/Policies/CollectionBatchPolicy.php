<?php

namespace App\Policies;

use App\Models\CollectionBatch;
use App\Models\User;

class CollectionBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('batches.view');
    }

    public function view(User $user, CollectionBatch $batch): bool
    {
        return $user->can('batches.view') && $user->canAccessBranch($batch->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('batches.submit');
    }

    public function submit(User $user, CollectionBatch $batch): bool
    {
        return $user->can('batches.submit') && $user->canAccessBranch($batch->branch_id);
    }

    public function verify(User $user, CollectionBatch $batch): bool
    {
        return $user->can('batches.verify') && $user->canAccessBranch($batch->branch_id);
    }

    public function approveForLodgement(User $user, CollectionBatch $batch): bool
    {
        return $user->can('batches.approve_lodgement') && $user->canAccessBranch($batch->branch_id);
    }

    public function close(User $user, CollectionBatch $batch): bool
    {
        return $user->can('batches.close') && $user->canAccessBranch($batch->branch_id);
    }
}
