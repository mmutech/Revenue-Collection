<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum PaymentStatus: string implements WorkflowStatus
{
    case Pending = 'pending';
    case Received = 'received';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Reversed => 'Reversed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Received => 'blue',
            self::Verified => 'emerald',
            self::Rejected => 'rose',
            self::Cancelled => 'slate',
            self::Reversed => 'amber',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Received->value, self::Cancelled->value],
            self::Received => [self::Verified->value, self::Rejected->value, self::Cancelled->value, self::Reversed->value],
            self::Verified => [self::Reversed->value],
            default => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function isCollected(): bool
    {
        return in_array($this, [self::Received, self::Verified], true);
    }

    public function countsAsVerifiedFunds(): bool
    {
        return $this === self::Verified;
    }
}
