<?php

namespace App\Enums;

enum VerificationDecision: string
{
    case Verified = 'verified';
    case Returned = 'returned';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Returned => 'Returned for correction',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Verified => 'emerald',
            self::Returned => 'amber',
            self::Rejected => 'rose',
        };
    }
}
