<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum TreatmentStatus: string implements WorkflowStatus
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Returned => 'Returned',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Submitted => 'blue',
            self::UnderReview => 'purple',
            self::Returned => 'amber',
            self::Approved => 'emerald',
            self::Rejected => 'rose',
            self::Completed => 'teal',
            self::Cancelled => 'slate',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted->value, self::Cancelled->value],
            self::Submitted => [self::UnderReview->value, self::Cancelled->value],
            self::UnderReview => [self::Approved->value, self::Rejected->value, self::Returned->value, self::Cancelled->value],
            self::Returned => [self::Submitted->value, self::Cancelled->value],
            self::Approved => [self::Completed->value, self::Cancelled->value],
            default => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Completed, self::Cancelled], true);
    }

    public function isOpen(): bool
    {
        return ! $this->isTerminal();
    }
}
