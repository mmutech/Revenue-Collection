<?php

namespace App\Enums\Concerns;

interface WorkflowStatus
{
    public function label(): string;

    /**
     * Semantic colour used by the UI badge component.
     */
    public function color(): string;

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(): array;

    public function isTerminal(): bool;
}
