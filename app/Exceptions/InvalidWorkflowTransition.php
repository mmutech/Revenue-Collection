<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidWorkflowTransition extends RuntimeException
{
    public static function between(string $from, string $to): self
    {
        return new self("The transition from [{$from}] to [{$to}] is not permitted.");
    }
}
