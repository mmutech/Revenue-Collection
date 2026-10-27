<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Approved => 'emerald',
            self::Rejected => 'rose',
            self::Returned => 'amber',
        };
    }
}
