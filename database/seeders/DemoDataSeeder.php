<?php

namespace Database\Seeders;

use App\Enums\ChequeStatus;
use App\Enums\DiscrepancyType;
use App\Enums\VerificationDecision;
use App\Models\Account;
use App\Models\AccountTreatmentReason;
use App\Models\AccountTreatmentType;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\User;
use App\Services\AccountTreatmentService;
use App\Services\CashierShiftService;
use App\Services\CollectionBatchService;
use App\Services\DiscrepancyService;
use App\Services\LodgementService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Payment::query()->exists()) {
            return;
        }

        $payments = app(PaymentService::class);
        $shifts = app(CashierShiftService::class);
        $batches = app(CollectionBatchService::class);
        $lodgements = app(LodgementService::class);
        $discrepancies = app(DiscrepancyService::class);
        $treatments = app(AccountTreatmentService::class);

        $cashier = User::query()->where('email', 'cashier@example.com')->firstOrFail();
        $cashier2 = User::query()->where('email', 'cashier2@example.com')->firstOrFail();
        $cashier3 = User::query()->where('email', 'cashier3@example.com')->firstOrFail();
        $clearing = User::query()->where('email', 'clearing@example.com')->firstOrFail();
        $supervisor = User::query()->where('email', 'supervisor@example.com')->firstOrFail();
        $bankOfficer = User::query()->where('email', 'bank@example.com')->firstOrFail();
        $treasury = User::query()->where('email', 'treasury@example.com')->firstOrFail();

        $hq = Branch::query()->where('code', 'HQ')->firstOrFail();
        $abuja = Branch::query()->where('code', 'ABJ')->firstOrFail();

        $channels = PaymentChannel::query()->pluck('id', 'code');
        $bank = Bank::query()->where('code', 'GTB')->firstOrFail();
        $bankBranch = $bank->bankBranches()->first();
        $abujaBank = Bank::query()->where('code', 'ZEN')->firstOrFail();

        $today = today()->toDateString();

        // ---------------------------------------------------------------
        // Cycle 1 (HQ): capture → batch → verify → approve → lodge → treasury
        // ---------------------------------------------------------------
        $shiftOne = $shifts->open($cashier, [
            'branch_id' => $hq->id,
            'opening_float' => 15000,
            'notes' => 'Morning cash desk',
        ]);

        $payments->create([
            'channel_id' => $channels['cash'],
            'branch_id' => $hq->id,
            'payer_name' => 'Adebayo Ogunlesi',
            'payer_phone' => '+234 803 000 0003',
            'amount' => 125000,
            'payment_date' => $today,
            'notes' => 'Land use charge',
        ], $cashier);

        $payments->create([
            'channel_id' => $channels['cash'],
            'branch_id' => $hq->id,
            'payer_name' => 'Lagos Marine Logistics Ltd',
            'payer_phone' => '+234 803 000 0001',
            'amount' => 87500,
            'payment_date' => $today,
            'notes' => 'Operating permit renewal',
        ], $cashier);

        $payments->create([
            'channel_id' => $channels['pos'],
            'branch_id' => $hq->id,
            'payer_name' => 'Zainab Mohammed',
            'amount' => 45000,
            'payment_date' => $today,
            'external_reference' => 'POS-'.strtoupper(uniqid()),
            'terminal_id' => 'TERM-HQ-01',
            'settlement_date' => $today,
        ], $cashier);

        $payments->create([
            'channel_id' => $channels['aes'],
            'branch_id' => $hq->id,
            'payer_name' => 'Abuja Health Services',
            'amount' => 320000,
            'payment_date' => $today,
            'external_reference' => 'AES-'.strtoupper(uniqid()),
            'provider' => 'AES Clearing',
            'settlement_date' => $today,
        ], $cashier);

        $payments->create([
            'channel_id' => $channels['direct_pay'],
            'branch_id' => $hq->id,
            'payer_name' => 'Rivers Agro Cooperative',
            'amount' => 210000,
            'payment_date' => $today,
            'external_reference' => 'DP-'.strtoupper(uniqid()),
            'bank_id' => $bank->id,
        ], $cashier);

        $cheque = $payments->create([
            'channel_id' => $channels['cheque'],
            'branch_id' => $hq->id,
            'payer_name' => 'Lagos Marine Logistics Ltd',
            'amount' => 150000,
            'payment_date' => $today,
            'bank_id' => $bank->id,
            'bank_branch_id' => $bankBranch?->id,
            'cheque_number' => 'CHQ-'.random_int(100000, 999999),
            'cheque_date' => $today,
            'cheque_drawer' => 'Lagos Marine Logistics Ltd',
        ], $cashier);

        $payments->clearCheque($cheque, $clearing, ChequeStatus::Cleared, 'Confirmed by bank on '.$today.'.');

        $summary = $shifts->summary($shiftOne);
        $shifts->close($shiftOne, $cashier, $summary['expected'], 'Balanced at close.');

        $batchOne = $batches->create($cashier, ['branch_id' => $hq->id, 'shift_id' => $shiftOne->id]);
        $batches->submit($batchOne, $cashier, $batchOne->expected_total);

        $batchOne = $batchOne->refresh();
        $batches->verify($batchOne, $clearing, VerificationDecision::Verified, $batchOne->expected_total, 'All collections counted and agree.');
        $batches->approveForLodgement($batchOne->refresh(), $supervisor);

        $lodgement = $lodgements->create($bankOfficer, [
            'branch_id' => $hq->id,
            'bank_id' => $bank->id,
            'bank_branch_id' => $bankBranch?->id,
            'deposit_slip_number' => 'SLIP-'.random_int(100000, 999999),
            'deposit_date' => $today,
            'deposited_by_name' => 'Grace Nwosu',
            'notes' => 'Consolidated daily lodgement.',
        ], [
            ['batch_id' => $batchOne->id, 'amount' => $batchOne->refresh()->expected_total],
        ]);

        $lodgements->uploadDocument(
            $lodgement,
            $bankOfficer,
            UploadedFile::fake()->create('deposit-slip.pdf', 120, 'application/pdf'),
            'deposit_slip'
        );
        $lodgements->submit($lodgement, $bankOfficer);
        $lodgements->decideTreasury($lodgement->refresh(), $treasury, [
            'decision' => \App\Enums\TreasuryDecision::Confirmed,
            'deposited_amount' => $lodgement->refresh()->amount,
            'bank_confirmation_reference' => 'BNK-'.random_int(100000, 999999),
            'notes' => 'Confirmed against bank statement.',
        ]);

        // ---------------------------------------------------------------
        // Cycle 2 (Abuja): submitted batch awaiting clearing verification
        // ---------------------------------------------------------------
        $shiftTwo = $shifts->open($cashier2, [
            'branch_id' => $abuja->id,
            'opening_float' => 10000,
            'notes' => 'Abuja morning shift',
        ]);

        $payments->create([
            'channel_id' => $channels['cash'],
            'branch_id' => $abuja->id,
            'payer_name' => 'Abuja Health Services',
            'amount' => 96000,
            'payment_date' => $today,
            'notes' => 'Clinic registration fees',
        ], $cashier2);

        $payments->create([
            'channel_id' => $channels['pos'],
            'branch_id' => $abuja->id,
            'payer_name' => 'Zainab Mohammed',
            'amount' => 32000,
            'payment_date' => $today,
            'external_reference' => 'POS-'.strtoupper(uniqid()),
            'terminal_id' => 'TERM-ABJ-01',
            'settlement_date' => $today,
        ], $cashier2);

        $summaryTwo = $shifts->summary($shiftTwo);
        $shifts->close($shiftTwo, $cashier2, $summaryTwo['expected'], 'Counted at close.');

        $batchTwo = $batches->create($cashier2, ['branch_id' => $abuja->id, 'shift_id' => $shiftTwo->id]);
        $batches->submit($batchTwo, $cashier2, $batchTwo->expected_total);

        // ---------------------------------------------------------------
        // Cycle 3 (HQ): approved and lodged, awaiting treasury confirmation
        // ---------------------------------------------------------------
        $shiftThree = $shifts->open($cashier3, [
            'branch_id' => $hq->id,
            'opening_float' => 5000,
            'notes' => 'POS terminal shift',
        ]);

        $payments->create([
            'channel_id' => $channels['cash'],
            'branch_id' => $hq->id,
            'payer_name' => 'Walk-in taxpayer',
            'amount' => 54000,
            'payment_date' => $today,
            'notes' => 'Miscellaneous revenue',
        ], $cashier3);

        $payments->create([
            'channel_id' => $channels['pos'],
            'branch_id' => $hq->id,
            'payer_name' => 'Adebayo Ogunlesi',
            'amount' => 18000,
            'payment_date' => $today,
            'external_reference' => 'POS-'.strtoupper(uniqid()),
            'terminal_id' => 'TERM-HQ-02',
            'settlement_date' => $today,
        ], $cashier3);

        $summaryThree = $shifts->summary($shiftThree);
        $shifts->close($shiftThree, $cashier3, $summaryThree['expected'], 'Balanced.');

        $batchThree = $batches->create($cashier3, ['branch_id' => $hq->id, 'shift_id' => $shiftThree->id]);
        $batches->submit($batchThree, $cashier3, $batchThree->expected_total);
        $batches->verify($batchThree->refresh(), $clearing, VerificationDecision::Verified, $batchThree->expected_total, 'Counted and agreed.');
        $batches->approveForLodgement($batchThree->refresh(), $supervisor);

        $pendingLodgement = $lodgements->create($bankOfficer, [
            'branch_id' => $hq->id,
            'bank_id' => $abujaBank->id,
            'deposit_slip_number' => 'SLIP-'.random_int(100000, 999999),
            'deposit_date' => $today,
            'deposited_by_name' => 'Grace Nwosu',
            'notes' => 'Afternoon lodgement awaiting treasury.',
        ], [
            ['batch_id' => $batchThree->id, 'amount' => $batchThree->refresh()->expected_total],
        ]);

        $lodgements->uploadDocument(
            $pendingLodgement,
            $bankOfficer,
            UploadedFile::fake()->create('teller-stamp.pdf', 96, 'application/pdf'),
            'deposit_slip'
        );
        $lodgements->submit($pendingLodgement, $bankOfficer);

        // ---------------------------------------------------------------
        // Exceptions and account treatment
        // ---------------------------------------------------------------
        $discrepancies->create($clearing, [
            'branch_id' => $hq->id,
            'type' => DiscrepancyType::MissingTransaction,
            'amount' => 27500,
            'description' => 'Bank alert received for ₦27,500 without a matching receipt. Under investigation by the cash desk.',
            'assigned_to' => $cashier->id,
        ]);

        $type = AccountTreatmentType::query()->where('code', 'write_off')->firstOrFail();
        $reason = AccountTreatmentReason::query()->where('account_treatment_type_id', $type->id)->first();
        $account = Account::query()->where('account_number', 'ACC-000003')->first()
            ?? Account::query()->firstOrFail();

        $treatment = $treatments->create($cashier, [
            'account_id' => $account->id,
            'account_treatment_type_id' => $type->id,
            'account_treatment_reason_id' => $reason?->id,
            'amount' => 48000,
            'description' => 'Customer balance confirmed uncollectible after three demand notices and a failed payment plan.',
            'effective_date' => $today,
        ]);

        $treatments->uploadDocument(
            $treatment,
            $cashier,
            UploadedFile::fake()->create('approval-memo.pdf', 88, 'application/pdf'),
            'approval_memo'
        );
        $treatments->uploadDocument(
            $treatment,
            $cashier,
            UploadedFile::fake()->create('demand-notices.pdf', 140, 'application/pdf'),
            'supporting_evidence'
        );
        $treatments->submit($treatment, $cashier);
        $treatments->decide($treatment->refresh(), $supervisor, \App\Enums\ApprovalDecision::Approved, 'Evidence reviewed and confirmed. Forwarded for management approval.');
    }
}
