<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum BatchStatus: string implements WorkflowStatus
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingVerification = 'pending_verification';
    case ReturnedForCorrection = 'returned_for_correction';
    case Verified = 'verified';
    case ApprovedForLodgement = 'approved_for_lodgement';
    case PartiallyLodged = 'partially_lodged';
    case FullyLodged = 'fully_lodged';
    case TreasuryReview = 'treasury_review';
    case Reconciled = 'reconciled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::PendingVerification => 'Pending verification',
            self::ReturnedForCorrection => 'Returned for correction',
            self::Verified => 'Verified',
            self::ApprovedForLodgement => 'Approved for lodgement',
            self::PartiallyLodged => 'Partially lodged',
            self::FullyLodged => 'Fully lodged',
            self::TreasuryReview => 'Treasury review',
            self::Reconciled => 'Reconciled',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Submitted, self::PendingVerification => 'blue',
            self::ReturnedForCorrection => 'amber',
            self::Verified => 'violet',
            self::ApprovedForLodgement => 'indigo',
            self::PartiallyLodged => 'orange',
            self::FullyLodged => 'teal',
            self::TreasuryReview => 'purple',
            self::Reconciled => 'emerald',
            self::Closed => 'slate',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted->value],
            self::Submitted => [self::PendingVerification->value],
            self::PendingVerification => [self::Verified->value, self::ReturnedForCorrection->value],
            self::ReturnedForCorrection => [self::Submitted->value],
            self::Verified => [self::ApprovedForLodgement->value],
            self::ApprovedForLodgement => [self::PartiallyLodged->value, self::FullyLodged->value],
            self::PartiallyLodged => [self::FullyLodged->value, self::ApprovedForLodgement->value],
            self::FullyLodged => [self::TreasuryReview->value, self::PartiallyLodged->value, self::ApprovedForLodgement->value],
            self::TreasuryReview => [self::Reconciled->value, self::PartiallyLodged->value, self::ApprovedForLodgement->value],
            self::Reconciled => [self::Closed->value],
            self::Closed => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::ReturnedForCorrection], true);
    }

    public function isAwaitingLodgement(): bool
    {
        return in_array($this, [self::ApprovedForLodgement, self::PartiallyLodged], true);
    }
}
