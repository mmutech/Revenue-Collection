<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum LodgementStatus: string implements WorkflowStatus
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderTreasuryReview = 'under_treasury_review';
    case Verified = 'verified';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Reconciled = 'reconciled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderTreasuryReview => 'Under treasury review',
            self::Verified => 'Treasury verified',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
            self::Reconciled => 'Reconciled',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Submitted => 'blue',
            self::UnderTreasuryReview => 'purple',
            self::Verified => 'emerald',
            self::Returned => 'amber',
            self::Rejected => 'rose',
            self::Reconciled => 'teal',
            self::Closed => 'slate',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted->value],
            self::Submitted => [self::UnderTreasuryReview->value],
            self::UnderTreasuryReview => [self::Verified->value, self::Returned->value, self::Rejected->value],
            self::Returned => [self::Draft->value],
            self::Verified => [self::Reconciled->value],
            self::Reconciled => [self::Closed->value],
            default => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    public function releasesAllocations(): bool
    {
        return in_array($this, [self::Returned, self::Rejected], true);
    }

    public function countsAsLodgedFunds(): bool
    {
        return in_array($this, [self::Verified, self::Reconciled, self::Closed], true);
    }
}
