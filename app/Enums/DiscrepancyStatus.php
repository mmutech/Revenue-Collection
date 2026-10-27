<?php

namespace App\Enums;

use App\Enums\Concerns\WorkflowStatus;

enum DiscrepancyStatus: string implements WorkflowStatus
{
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case AwaitingEvidence = 'awaiting_evidence';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderInvestigation => 'Under investigation',
            self::AwaitingEvidence => 'Awaiting evidence',
            self::Resolved => 'Resolved',
            self::Escalated => 'Escalated',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'rose',
            self::UnderInvestigation => 'amber',
            self::AwaitingEvidence => 'orange',
            self::Resolved => 'emerald',
            self::Escalated => 'purple',
            self::Closed => 'slate',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::UnderInvestigation->value, self::AwaitingEvidence->value, self::Escalated->value, self::Resolved->value],
            self::UnderInvestigation => [self::AwaitingEvidence->value, self::Escalated->value, self::Resolved->value],
            self::AwaitingEvidence => [self::UnderInvestigation->value, self::Escalated->value, self::Resolved->value],
            self::Escalated => [self::UnderInvestigation->value, self::Resolved->value, self::Closed->value],
            self::Resolved => [self::Closed->value],
            self::Closed => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed], true);
    }
}
