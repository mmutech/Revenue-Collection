<?php

namespace App\Enums;

enum DiscrepancyType: string
{
    case Shortage = 'shortage';
    case Overage = 'overage';
    case Duplicate = 'duplicate';
    case MissingTransaction = 'missing_transaction';
    case UnmatchedLodgement = 'unmatched_lodgement';
    case AmountMismatch = 'amount_mismatch';
    case DishonouredCheque = 'dishonoured_cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Shortage => 'Shortage',
            self::Overage => 'Overage',
            self::Duplicate => 'Duplicate',
            self::MissingTransaction => 'Missing transaction',
            self::UnmatchedLodgement => 'Unmatched lodgement',
            self::AmountMismatch => 'Amount mismatch',
            self::DishonouredCheque => 'Dishonoured cheque',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Shortage, self::MissingTransaction, self::DishonouredCheque => 'rose',
            self::Overage => 'blue',
            self::Duplicate => 'purple',
            self::UnmatchedLodgement, self::AmountMismatch => 'amber',
            self::Other => 'slate',
        };
    }
}
