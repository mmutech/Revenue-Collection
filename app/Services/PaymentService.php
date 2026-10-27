<?php

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\AdjustmentType;
use App\Enums\ChequeStatus;
use App\Enums\DiscrepancyType;
use App\Enums\PaymentStatus;
use App\Events\ChequeStatusChanged;
use App\Events\PaymentRecorded;
use App\Events\PaymentReversed;
use App\Exceptions\FinancialRuleViolation;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\PaymentChannel;
use App\Models\PaymentReceipt;
use App\Models\User;
use App\Support\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly DiscrepancyService $discrepancies,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Payment
    {
        Gate::forUser($actor)->authorize('create', Payment::class);

        $channel = PaymentChannel::query()->findOrFail($data['channel_id']);
        $branchId = (int) $data['branch_id'];

        if (! $channel->is_active) {
            throw ValidationException::withMessages(['channel_id' => 'This payment channel is inactive.']);
        }

        if (! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the selected branch.');
        }

        $this->validateChannelRequirements($channel, $data);

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be greater than zero.']);
        }

        $paymentDate = CarbonImmutable::parse($data['payment_date']);
        if (! Setting::bool('payments.allow_future_date', false) && $paymentDate->isAfter(today())) {
            throw ValidationException::withMessages(['payment_date' => 'The payment date cannot be in the future.']);
        }

        if (! empty($data['idempotency_key'])) {
            $existing = Payment::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ((int) $existing->channel_id !== (int) $channel->id
                    || (int) $existing->branch_id !== $branchId
                    || (float) $existing->amount !== $amount) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'This idempotency key was already used with different payment details.',
                    ]);
                }

                return $existing->load(['channel', 'receipt', 'branch']);
            }
        }

        if (! empty($data['external_reference'])) {
            $duplicate = Payment::query()
                ->where('channel_id', $channel->id)
                ->where('external_reference', $data['external_reference'])
                ->whereNotIn('status', [PaymentStatus::Cancelled->value, PaymentStatus::Reversed->value, PaymentStatus::Rejected->value])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'external_reference' => 'This external reference has already been recorded for this channel.',
                ]);
            }
        }

        if ($channel->isCheque() && ! empty($data['cheque_number'])) {
            $duplicateCheque = Payment::query()
                ->where('bank_id', $data['bank_id'] ?? null)
                ->where('cheque_number', $data['cheque_number'])
                ->whereNotIn('cheque_status', [ChequeStatus::Cancelled->value, ChequeStatus::Dishonoured->value])
                ->exists();

            if ($duplicateCheque) {
                throw ValidationException::withMessages([
                    'cheque_number' => 'This cheque has already been captured.',
                ]);
            }
        }

        $shift = $this->resolveShift($data, $actor, $channel, $branchId);

        $payment = DB::transaction(function () use ($data, $actor, $channel, $branchId, $amount, $paymentDate, $shift) {
            $payment = Payment::query()->create([
                'reference' => $this->numbers->paymentReference(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'channel_id' => $channel->id,
                'branch_id' => $branchId,
                'collection_point_id' => $data['collection_point_id'] ?? $actor->collection_point_id,
                'cashier_id' => $data['cashier_id'] ?? $actor->id,
                'shift_id' => $shift?->id,
                'customer_id' => $data['customer_id'] ?? null,
                'payer_name' => $data['payer_name'],
                'payer_phone' => $data['payer_phone'] ?? null,
                'payer_email' => $data['payer_email'] ?? null,
                'amount' => $amount,
                'currency' => $data['currency'] ?? Setting::string('organisation.currency', 'NGN'),
                'payment_date' => $paymentDate->toDateString(),
                'external_reference' => $data['external_reference'] ?? null,
                'provider' => $data['provider'] ?? null,
                'settlement_date' => $data['settlement_date'] ?? null,
                'bank_id' => $data['bank_id'] ?? null,
                'bank_branch_id' => $data['bank_branch_id'] ?? null,
                'terminal_id' => $data['terminal_id'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'cheque_drawer' => $data['cheque_drawer'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $actor->id,
            ]);

            $payment->refresh();
            $payment->transitionTo(PaymentStatus::Received, $actor, 'Payment captured through '.$channel->name);

            if ($channel->isCheque()) {
                $payment->forceFill([
                    'cheque_status' => ChequeStatus::PendingClearance->value,
                ])->save();
            }

            if ($channel->requires_receipt || $channel->isCash()) {
                PaymentReceipt::query()->create([
                    'payment_id' => $payment->id,
                    'receipt_number' => $this->numbers->receiptNumber(),
                    'status' => 'issued',
                    'issued_by' => $actor->id,
                    'issued_at' => now(),
                ]);
            }

            return $payment->refresh();
        });

        PaymentRecorded::dispatch($payment);

        return $payment->load(['channel', 'receipt', 'branch', 'cashier']);
    }

    public function cancel(Payment $payment, User $actor, string $reason): Payment
    {
        Gate::forUser($actor)->authorize('cancel', $payment);

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A cancellation reason is required.']);
        }

        $payment = DB::transaction(function () use ($payment, $actor, $reason) {
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->batchItem()->exists()) {
                throw FinancialRuleViolation::because('A payment that has been added to a collection batch cannot be cancelled.');
            }

            if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Received], true)) {
                throw FinancialRuleViolation::because('Only pending or received payments can be cancelled.');
            }

            $payment->fill([
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $payment->transitionTo(PaymentStatus::Cancelled, $actor, $reason);

            $payment->receipt()->update(['status' => 'void']);

            return $payment->refresh();
        });

        return $payment;
    }

    public function clearCheque(Payment $payment, User $actor, ChequeStatus $chequeStatus, ?string $notes = null): Payment
    {
        Gate::forUser($actor)->authorize('clearCheque', $payment);

        if (! in_array($chequeStatus, [ChequeStatus::Cleared, ChequeStatus::Dishonoured], true)) {
            throw ValidationException::withMessages(['cheque_status' => 'Only cleared or dishonoured outcomes can be recorded.']);
        }

        if ($chequeStatus === ChequeStatus::Dishonoured && trim((string) $notes) === '') {
            throw ValidationException::withMessages(['notes' => 'A reason is required when a cheque is dishonoured.']);
        }

        $payment = DB::transaction(function () use ($payment, $actor, $chequeStatus, $notes) {
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $payment->isCheque()) {
                throw FinancialRuleViolation::because('This payment is not a cheque.');
            }

            if (! in_array($payment->cheque_status, [ChequeStatus::Received, ChequeStatus::PendingClearance], true)) {
                throw FinancialRuleViolation::because('This cheque is not awaiting clearance.');
            }

            if ($payment->batchItem()->exists()) {
                throw FinancialRuleViolation::because('This cheque is already part of a collection batch.');
            }

            $payment->forceFill(['cheque_status' => $chequeStatus->value])->save();

            if ($chequeStatus === ChequeStatus::Cleared) {
                $payment->fill([
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ])->save();

                $payment->transitionTo(PaymentStatus::Verified, $actor, 'Cheque clearance confirmed');
            } else {
                $payment->transitionTo(PaymentStatus::Rejected, $actor, $notes);

                $this->discrepancies->create($actor, [
                    'payment_id' => $payment->id,
                    'branch_id' => $payment->branch_id,
                    'type' => DiscrepancyType::DishonouredCheque,
                    'amount' => $payment->amount,
                    'description' => 'Dishonoured cheque '.$payment->cheque_number.'. '.$notes,
                ]);
            }

            return $payment->refresh();
        });

        ChequeStatusChanged::dispatch($payment, $chequeStatus->value);

        return $payment;
    }

    public function requestReversal(Payment $payment, User $actor, string $reason): PaymentAdjustment
    {
        Gate::forUser($actor)->authorize('reverse', $payment);

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reversal reason is required.']);
        }

        return DB::transaction(function () use ($payment, $actor, $reason) {
            /** @var Payment $payment */
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! in_array($payment->status, [PaymentStatus::Received, PaymentStatus::Verified], true)) {
                throw FinancialRuleViolation::because('Only received or verified payments can be reversed.');
            }

            if ($payment->batchItem()->exists() || $payment->isDeposited()) {
                throw FinancialRuleViolation::because('A payment that has been batched or deposited cannot be reversed.');
            }

            if ($payment->adjustments()->where('status', AdjustmentStatus::Pending->value)->exists()) {
                throw FinancialRuleViolation::because('A reversal request is already pending for this payment.');
            }

            $adjustment = PaymentAdjustment::query()->create([
                'reference' => $this->numbers->adjustmentReference(),
                'payment_id' => $payment->id,
                'type' => AdjustmentType::Reversal->value,
                'amount' => $payment->amount,
                'reason' => $reason,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            if (! Setting::bool('payments.require_reversal_approval', true)) {
                return $this->applyReversal($adjustment, $actor, 'Auto-approved by configuration');
            }

            return $adjustment->refresh();
        });
    }

    public function decideReversal(PaymentAdjustment $adjustment, User $actor, bool $approved, ?string $notes = null): PaymentAdjustment
    {
        Gate::forUser($actor)->authorize('approveReversal', $adjustment->payment);

        if ($adjustment->requested_by === $actor->id) {
            throw FinancialRuleViolation::because('You cannot approve a reversal that you requested.');
        }

        return DB::transaction(function () use ($adjustment, $actor, $approved, $notes) {
            /** @var PaymentAdjustment $adjustment */
            $adjustment = PaymentAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            if ($adjustment->status !== AdjustmentStatus::Pending) {
                throw FinancialRuleViolation::because('This reversal has already been decided.');
            }

            if (! $approved) {
                $adjustment->fill([
                    'status' => AdjustmentStatus::Rejected->value,
                    'decided_by' => $actor->id,
                    'decided_at' => now(),
                    'decision_notes' => $notes,
                ])->save();

                return $adjustment->refresh();
            }

            return $this->applyReversal($adjustment, $actor, $notes);
        });
    }

    private function applyReversal(PaymentAdjustment $adjustment, User $actor, ?string $notes): PaymentAdjustment
    {
        /** @var Payment $payment */
        $payment = Payment::query()->lockForUpdate()->findOrFail($adjustment->payment_id);

        if ($payment->batchItem()->exists() || $payment->isDeposited()) {
            throw FinancialRuleViolation::because('The payment is now batched or deposited and can no longer be reversed.');
        }

        if (! in_array($payment->status, [PaymentStatus::Received, PaymentStatus::Verified], true)) {
            throw FinancialRuleViolation::because('The payment status no longer allows reversal.');
        }

        $payment->fill([
            'reversed_at' => now(),
            'reversal_reason' => $adjustment->reason,
        ])->save();
        $payment->transitionTo(PaymentStatus::Reversed, $actor, $adjustment->reason);

        $adjustment->fill([
            'status' => AdjustmentStatus::Approved->value,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_notes' => $notes,
        ])->save();

        PaymentReversed::dispatch($payment, $adjustment->reason);

        return $adjustment->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveShift(array $data, User $actor, PaymentChannel $channel, int $branchId): ?CashierShift
    {
        $shift = null;

        if (! empty($data['shift_id'])) {
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($data['shift_id']);
        } else {
            $shift = $actor->openShift();
        }

        if ($shift) {
            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['shift_id' => 'The selected shift is closed.']);
            }

            if ((int) $shift->user_id !== (int) $actor->id) {
                throw ValidationException::withMessages(['shift_id' => 'The selected shift belongs to another cashier.']);
            }

            if ((int) $shift->branch_id !== $branchId) {
                throw ValidationException::withMessages(['shift_id' => 'The shift does not belong to the selected branch.']);
            }
        }

        if ($channel->isCash() && ! $shift) {
            throw ValidationException::withMessages([
                'shift_id' => 'Open a cashier shift before recording a cash payment.',
            ]);
        }

        return $shift;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateChannelRequirements(PaymentChannel $channel, array $data): void
    {
        $requirements = [
            'requires_external_reference' => ['external_reference', 'An external transaction reference is required for this channel.'],
            'requires_bank' => ['bank_id', 'A bank is required for this channel.'],
            'requires_bank_branch' => ['bank_branch_id', 'A bank branch is required for this channel.'],
            'requires_terminal' => ['terminal_id', 'A terminal identifier is required for POS payments.'],
            'requires_settlement_date' => ['settlement_date', 'A settlement date is required for this channel.'],
        ];

        $errors = [];

        foreach ($requirements as $flag => [$field, $message]) {
            if ($channel->{$flag} && blank($data[$field] ?? null)) {
                $errors[$field] = $message;
            }
        }

        if ($channel->requires_cheque_details) {
            foreach ([
                'cheque_number' => 'A cheque number is required.',
                'cheque_date' => 'The cheque date is required.',
                'cheque_drawer' => 'The drawer name is required.',
            ] as $field => $message) {
                if (blank($data[$field] ?? null)) {
                    $errors[$field] = $message;
                }
            }
        }

        foreach (($channel->required_fields ?? []) as $field) {
            if (blank($data[$field] ?? null)) {
                $errors[$field] = 'The '.str($field)->replace('_', ' ').' field is required for this channel.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
