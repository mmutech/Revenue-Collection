<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view') && $user->canAccessBranch($payment->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.create')
            && $user->canAccessBranch($payment->branch_id)
            && ! $payment->isLockedInBatch();
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->can('payments.cancel') && $user->canAccessBranch($payment->branch_id);
    }

    public function reverse(User $user, Payment $payment): bool
    {
        return $user->can('payments.reverse') && $user->canAccessBranch($payment->branch_id);
    }

    public function approveReversal(User $user, Payment $payment): bool
    {
        return $user->can('payments.approve_reversal') && $user->canAccessBranch($payment->branch_id);
    }

    public function clearCheque(User $user, Payment $payment): bool
    {
        return $user->can('payments.cheque_clear') && $user->canAccessBranch($payment->branch_id);
    }
}
