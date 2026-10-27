<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return $this === self::Open ? 'emerald' : 'slate';
    }
}
