<?php

namespace App\Enums;

enum TreasuryDecision: string
{
    case Confirmed = 'confirmed';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
            self::Escalated => 'Escalated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Confirmed => 'emerald',
            self::Returned => 'amber',
            self::Rejected => 'rose',
            self::Escalated => 'purple',
        };
    }
}
