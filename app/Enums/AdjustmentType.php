<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Reversal = 'reversal';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
