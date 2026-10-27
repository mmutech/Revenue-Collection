<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum ChequeStatus: string implements WorkflowStatus
{
    case Received = 'received';
    case PendingClearance = 'pending_clearance';
    case Cleared = 'cleared';
    case Dishonoured = 'dishonoured';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::PendingClearance => 'Pending clearance',
            self::Cleared => 'Cleared',
            self::Dishonoured => 'Dishonoured',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'slate',
            self::PendingClearance => 'amber',
            self::Cleared => 'emerald',
            self::Dishonoured => 'rose',
            self::Cancelled => 'slate',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Received => [self::PendingClearance->value, self::Cancelled->value],
            self::PendingClearance => [self::Cleared->value, self::Dishonoured->value, self::Cancelled->value],
            default => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
