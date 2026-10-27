<?php

namespace App\Services;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Events\DiscrepancyRecorded;
use App\Models\CollectionDiscrepancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DiscrepancyService
{
    public function __construct(private readonly DocumentNumberService $numbers) {}

    /**
     * @param  array{batch_id?: int|null, payment_id?: int|null, lodgement_id?: int|null, branch_id: int, type: DiscrepancyType|string, amount: int|float|string, description: string, assigned_to?: int|null}  $data
     */
    public function create(User $actor, array $data): CollectionDiscrepancy
    {
        $discrepancy = DB::transaction(function () use ($actor, $data) {
            $discrepancy = CollectionDiscrepancy::query()->create([
                'reference' => $this->numbers->discrepancyReference(),
                'collection_batch_id' => $data['batch_id'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'branch_id' => $data['branch_id'],
                'bank_lodgement_id' => $data['lodgement_id'] ?? null,
                'type' => $data['type'] instanceof DiscrepancyType ? $data['type']->value : $data['type'],
                'amount' => round((float) $data['amount'], 2),
                'description' => $data['description'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $actor->id,
            ]);

            $discrepancy->transitionTo(DiscrepancyStatus::Open, $actor, 'Discrepancy recorded');

            return $discrepancy->refresh();
        });

        DiscrepancyRecorded::dispatch($discrepancy);

        return $discrepancy;
    }

    public function resolve(User $actor, CollectionDiscrepancy $discrepancy, string $resolution): CollectionDiscrepancy
    {
        Gate::forUser($actor)->authorize('manage', $discrepancy);

        return DB::transaction(function () use ($actor, $discrepancy, $resolution) {
            $discrepancy = CollectionDiscrepancy::query()->lockForUpdate()->findOrFail($discrepancy->id);
            $discrepancy->fill([
                'resolution' => $resolution,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);
            $discrepancy->save();

            if ($discrepancy->status !== DiscrepancyStatus::Resolved) {
                $discrepancy->transitionTo(DiscrepancyStatus::Resolved, $actor, $resolution);
            }

            return $discrepancy->refresh();
        });
    }

    public function escalate(User $actor, CollectionDiscrepancy $discrepancy, User $to, string $reason): CollectionDiscrepancy
    {
        Gate::forUser($actor)->authorize('escalate', $discrepancy);

        return DB::transaction(function () use ($actor, $discrepancy, $to, $reason) {
            $discrepancy = CollectionDiscrepancy::query()->lockForUpdate()->findOrFail($discrepancy->id);
            $discrepancy->fill([
                'escalated_to' => $to->id,
                'escalated_at' => now(),
            ]);
            $discrepancy->save();

            if ($discrepancy->status !== DiscrepancyStatus::Escalated) {
                $discrepancy->transitionTo(DiscrepancyStatus::Escalated, $actor, $reason);
            }

            return $discrepancy->refresh();
        });
    }

    public function transition(User $actor, CollectionDiscrepancy $discrepancy, DiscrepancyStatus $to, ?string $reason = null): CollectionDiscrepancy
    {
        Gate::forUser($actor)->authorize('manage', $discrepancy);

        return DB::transaction(function () use ($actor, $discrepancy, $to, $reason) {
            $discrepancy = CollectionDiscrepancy::query()->lockForUpdate()->findOrFail($discrepancy->id);
            $discrepancy->transitionTo($to, $actor, $reason);

            return $discrepancy->refresh();
        });
    }
}
