<?php

namespace App\Support;

final class Money
{
    private const SYMBOLS = [
        'NGN' => '₦',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R',
    ];

    public static function format(int|float|string|null $amount, string $currency = 'NGN', bool $withCode = false): string
    {
        $value = number_format((float) ($amount ?? 0), 2);
        $symbol = self::SYMBOLS[strtoupper($currency)] ?? null;

        if ($symbol === null) {
            return strtoupper($currency).' '.$value;
        }

        return $withCode ? $symbol.$value.' '.strtoupper($currency) : $symbol.$value;
    }

    public static function compact(int|float|string|null $amount, string $currency = 'NGN'): string
    {
        $value = (float) ($amount ?? 0);
        $symbol = self::SYMBOLS[strtoupper($currency)] ?? strtoupper($currency).' ';

        return match (true) {
            abs($value) >= 1_000_000_000 => $symbol.number_format($value / 1_000_000_000, 1).'B',
            abs($value) >= 1_000_000 => $symbol.number_format($value / 1_000_000, 1).'M',
            abs($value) >= 1_000 => $symbol.number_format($value / 1_000, 1).'K',
            default => $symbol.number_format($value, 0),
        };
    }
}
