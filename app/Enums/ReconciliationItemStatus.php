<?php

namespace App\Enums;

enum ReconciliationItemStatus: string
{
    case Matched = 'matched';
    case Unmatched = 'unmatched';
    case Variance = 'variance';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Matched => 'emerald',
            self::Unmatched => 'rose',
            self::Variance => 'amber',
        };
    }
}
