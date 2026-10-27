<?php

namespace App\Models\Concerns;

use App\Enums\Concerns\WorkflowStatus;
use App\Exceptions\InvalidWorkflowTransition;
use App\Models\User;
use App\Models\WorkflowTransition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HasWorkflow
{
    public function transitionTo(
        WorkflowStatus $to,
        ?User $actor = null,
        ?string $reason = null,
        array $metadata = [],
    ): static {
        return DB::transaction(function () use ($to, $actor, $reason, $metadata) {
            $from = $this->status;

            if ($from instanceof WorkflowStatus && ! in_array($to->value, $from->allowedTransitions(), true)) {
                throw InvalidWorkflowTransition::between($from->label(), $to->label());
            }

            $fromValue = $from instanceof WorkflowStatus ? $from->value : $from;

            $this->forceFill(['status' => $to->value])->save();

            WorkflowTransition::query()->create([
                'subject_type' => $this->getMorphClass(),
                'subject_id' => $this->getKey(),
                'from_status' => $fromValue,
                'to_status' => $to->value,
                'actor_id' => $actor?->getKey() ?? Auth::id(),
                'reason' => $reason,
                'metadata' => $metadata ?: null,
                'created_at' => now(),
            ]);

            return $this;
        });
    }

    public function canTransitionTo(WorkflowStatus $to): bool
    {
        $from = $this->status;

        return $from instanceof WorkflowStatus
            && in_array($to->value, $from->allowedTransitions(), true);
    }
}
