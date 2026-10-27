<?php

namespace App\Enums;

enum PaymentChannelCode: string
{
    case Aes = 'aes';
    case DirectPay = 'direct_pay';
    case Cash = 'cash';
    case Pos = 'pos';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Aes => 'AES — Automated Electronic Settlement',
            self::DirectPay => 'Direct Pay',
            self::Cash => 'Cash',
            self::Pos => 'POS',
            self::Cheque => 'Cheque',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Aes => 'AES',
            self::DirectPay => 'Direct Pay',
            self::Cash => 'Cash',
            self::Pos => 'POS',
            self::Cheque => 'Cheque',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aes => 'violet',
            self::DirectPay => 'blue',
            self::Cash => 'emerald',
            self::Pos => 'orange',
            self::Cheque => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Aes => 'bolt',
            self::DirectPay => 'building-library',
            self::Cash => 'banknotes',
            self::Pos => 'credit-card',
            self::Cheque => 'document-text',
        };
    }
}
