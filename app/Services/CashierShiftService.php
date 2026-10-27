<?php

namespace App\Services;

use App\Enums\DiscrepancyType;
use App\Enums\PaymentStatus;
use App\Enums\ShiftStatus;
use App\Exceptions\FinancialRuleViolation;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CashierShiftService
{
    public function __construct(private readonly DiscrepancyService $discrepancies) {}

    /**
     * @param  array{branch_id?: int, collection_point_id?: int|null, opening_float?: int|float|string, notes?: string|null}  $data
     */
    public function open(User $actor, array $data): CashierShift
    {
        Gate::forUser($actor)->authorize('create', CashierShift::class);

        $branchId = (int) ($data['branch_id'] ?? $actor->branch_id);

        if (! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the selected branch.');
        }

        return DB::transaction(function () use ($actor, $data, $branchId) {
            $hasOpenShift = CashierShift::query()
                ->where('user_id', $actor->id)
                ->where('status', ShiftStatus::Open->value)
                ->lockForUpdate()
                ->exists();

            if ($hasOpenShift) {
                throw FinancialRuleViolation::because('You already have an open shift. Close it before opening another.');
            }

            $businessDate = isset($data['business_date'])
                ? CarbonImmutable::parse($data['business_date'])
                : CarbonImmutable::today();

            if ($businessDate->isAfter(CarbonImmutable::today())) {
                throw ValidationException::withMessages(['business_date' => 'A shift cannot be opened for a future date.']);
            }

            return CashierShift::query()->create([
                'user_id' => $actor->id,
                'branch_id' => $branchId,
                'collection_point_id' => $data['collection_point_id'] ?? $actor->collection_point_id,
                'business_date' => $businessDate->toDateString(),
                'opening_float' => round((float) ($data['opening_float'] ?? 0), 2),
                'status' => ShiftStatus::Open->value,
                'notes' => $data['notes'] ?? null,
                'opened_at' => now(),
            ]);
        });
    }

    public function expectedCash(CashierShift $shift): string
    {
        $collections = Payment::query()
            ->where('shift_id', $shift->id)
            ->whereIn('status', [PaymentStatus::Received->value, PaymentStatus::Verified->value])
            ->whereHas('channel', fn ($q) => $q->where('code', 'cash'))
            ->sum('amount');

        return number_format((float) $shift->opening_float + (float) $collections, 2, '.', '');
    }

    public function close(CashierShift $shift, User $actor, int|float|string $countedCash, ?string $notes = null): CashierShift
    {
        Gate::forUser($actor)->authorize('close', $shift);

        if ((int) $shift->user_id !== (int) $actor->id && ! $actor->canAccessAllBranches()) {
            throw new AuthorizationException('Only the shift owner or a group supervisor can close this shift.');
        }

        $counted = round((float) $countedCash, 2);
        if ($counted < 0) {
            throw ValidationException::withMessages(['counted_cash' => 'Counted cash cannot be negative.']);
        }

        $shift = DB::transaction(function () use ($shift, $actor, $counted, $notes) {
            /** @var CashierShift $shift */
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            if (! $shift->isOpen()) {
                throw FinancialRuleViolation::because('This shift is already closed.');
            }

            $expected = (float) $this->expectedCash($shift);
            $variance = round($counted - $expected, 2);

            $shift->forceFill([
                'expected_cash' => number_format($expected, 2, '.', ''),
                'counted_cash' => number_format($counted, 2, '.', ''),
                'variance' => number_format($variance, 2, '.', ''),
                'status' => ShiftStatus::Closed->value,
                'closed_at' => now(),
                'closed_by' => $actor->id,
                'notes' => $notes ?? $shift->notes,
            ])->save();

            if (abs($variance) >= 0.01) {
                $this->discrepancies->create($actor, [
                    'branch_id' => $shift->branch_id,
                    'type' => $variance < 0 ? DiscrepancyType::Shortage : DiscrepancyType::Overage,
                    'amount' => abs($variance),
                    'description' => 'Shift '.$shift->id.' cash count variance of '.number_format($variance, 2)
                        .' for business date '.$shift->business_date->toDateString().'. '.$notes,
                    'assigned_to' => $shift->user_id,
                ]);
            }

            return $shift->refresh();
        });

        return $shift;
    }

    /**
     * @return array{expected: string, counted: string, variance: string, collections: string}
     */
    public function summary(CashierShift $shift): array
    {
        $collections = Payment::query()
            ->where('shift_id', $shift->id)
            ->whereIn('status', [PaymentStatus::Received->value, PaymentStatus::Verified->value])
            ->sum('amount');

        $expected = (float) $shift->opening_float + (float) $collections;
        $counted = (float) ($shift->counted_cash ?? 0);

        return [
            'expected' => number_format($expected, 2, '.', ''),
            'counted' => number_format($counted, 2, '.', ''),
            'variance' => number_format($counted - $expected, 2, '.', ''),
            'collections' => number_format((float) $collections, 2, '.', ''),
        ];
    }

    public function currentBusinessDate(): CarbonImmutable
    {
        return CarbonImmutable::today();
    }
}
