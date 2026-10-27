<?php

namespace App\Exceptions;

use RuntimeException;

class FinancialRuleViolation extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
