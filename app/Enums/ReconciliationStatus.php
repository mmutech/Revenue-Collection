<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Running => 'blue',
            self::Completed => 'emerald',
            self::Failed => 'rose',
        };
    }
}
