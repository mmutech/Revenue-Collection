<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTreatmentType;
use App\Models\ApprovalWorkflow;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\Branch;
use App\Models\CollectionPoint;
use App\Models\Customer;
use App\Models\PaymentChannel;
use App\Models\SystemSetting;
use App\Support\Setting;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['code' => 'HQ', 'name' => 'Head Office — Plateau', 'address' => '12 Marina Road, Jos Island, Jos', 'phone' => '+234 700 100 0001', 'email' => 'jos@revenue.example'],
            ['code' => 'JO', 'name' => 'Jos State Office', 'address' => 'Plot 44, Central Business District, Jos', 'phone' => '+234 700 100 0002', 'email' => 'jos@revenue.example'],
            ['code' => 'BA', 'name' => 'Bauchi State Office', 'address' => '8 Aba Road, Bauchi, Rivers', 'phone' => '+234 700 100 0003', 'email' => 'bauchi@revenue.example'],
        ];

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(['code' => $branch['code']], $branch + ['is_active' => true]);
        }

        $branchMap = Branch::query()->pluck('id', 'code');

        $points = [
            ['branch' => 'HQ', 'code' => 'HQ-CASH-01', 'name' => 'Head Office Cash Desk', 'type' => 'cashier'],
            ['branch' => 'HQ', 'code' => 'HQ-POS-01', 'name' => 'Head Office POS Terminal', 'type' => 'pos'],
            ['branch' => 'HQ', 'code' => 'HQ-AES-01', 'name' => 'Head Office Electronic Settlement', 'type' => 'electronic'],
            ['branch' => 'JO', 'code' => 'JO-CASH-01', 'name' => 'Jos Cash Desk', 'type' => 'cashier'],
            ['branch' => 'JO', 'code' => 'JO-POS-01', 'name' => 'Jos POS Terminal', 'type' => 'pos'],
            ['branch' => 'BA', 'code' => 'BA-CASH-01', 'name' => 'Bauchi Cash Desk', 'type' => 'cashier'],
        ];

        foreach ($points as $point) {
            CollectionPoint::query()->updateOrCreate(
                ['code' => $point['code']],
                [
                    'branch_id' => $branchMap[$point['branch']],
                    'name' => $point['name'],
                    'type' => $point['type'],
                    'is_active' => true,
                ]
            );
        }

        $banks = [
            ['code' => 'GTB', 'name' => 'Guaranty Trust Bank', 'short_name' => 'GTBank'],
            ['code' => 'ZEN', 'name' => 'Zenith Bank', 'short_name' => 'Zenith'],
            ['code' => 'ACC', 'name' => 'Access Bank', 'short_name' => 'Access'],
            ['code' => 'FBN', 'name' => 'First Bank of Nigeria', 'short_name' => 'FirstBank'],
            ['code' => 'UBA', 'name' => 'United Bank for Africa', 'short_name' => 'UBA'],
        ];

        foreach ($banks as $bank) {
            $model = Bank::query()->updateOrCreate(['code' => $bank['code']], $bank + ['is_active' => true]);

            foreach ([
                ['code' => 'MAIN', 'name' => $bank['short_name'].' Main Branch', 'sort_code' => '000'.$model->id],
                ['code' => 'CORP', 'name' => $bank['short_name'].' Corporate Branch', 'sort_code' => '058'.$model->id],
            ] as $bankBranch) {
                BankBranch::query()->updateOrCreate(
                    ['bank_id' => $model->id, 'code' => $bankBranch['code']],
                    $bankBranch + ['is_active' => true]
                );
            }
        }

        $channels = [
            [
                'code' => 'aes',
                'name' => 'AES — Automated Electronic Settlement',
                'description' => 'Automated electronic settlement from partner institutions.',
                'requires_receipt' => false,
                'requires_external_reference' => true,
                'requires_bank' => false,
                'requires_bank_branch' => false,
                'requires_terminal' => false,
                'requires_cheque_details' => false,
                'requires_settlement_date' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'direct_pay',
                'name' => 'Direct Pay',
                'description' => 'Direct bank transfer or direct debit instruction.',
                'requires_receipt' => false,
                'requires_external_reference' => true,
                'requires_bank' => true,
                'requires_bank_branch' => false,
                'requires_terminal' => false,
                'requires_cheque_details' => false,
                'requires_settlement_date' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'cash',
                'name' => 'Cash',
                'description' => 'Over-the-counter cash collection.',
                'requires_receipt' => true,
                'requires_external_reference' => false,
                'requires_bank' => false,
                'requires_bank_branch' => false,
                'requires_terminal' => false,
                'requires_cheque_details' => false,
                'requires_settlement_date' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'pos',
                'name' => 'POS',
                'description' => 'Card payment through a point-of-sale terminal.',
                'requires_receipt' => true,
                'requires_external_reference' => true,
                'requires_bank' => false,
                'requires_bank_branch' => false,
                'requires_terminal' => true,
                'requires_cheque_details' => false,
                'requires_settlement_date' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'cheque',
                'name' => 'Cheque',
                'description' => 'Bank cheque accepted at the cash desk, subject to clearance.',
                'requires_receipt' => true,
                'requires_external_reference' => false,
                'requires_bank' => true,
                'requires_bank_branch' => true,
                'requires_terminal' => false,
                'requires_cheque_details' => true,
                'requires_settlement_date' => false,
                'sort_order' => 5,
            ],
        ];

        foreach ($channels as $channel) {
            PaymentChannel::query()->updateOrCreate(['code' => $channel['code']], $channel + ['is_active' => true]);
        }

        $workflow = ApprovalWorkflow::query()->updateOrCreate(
            ['code' => 'treatment-standard'],
            [
                'name' => 'Standard account treatment approval',
                'subject_type' => 'App\\Models\\AccountTreatmentRequest',
                'description' => 'Supervisor review followed by manager approval.',
                'is_active' => true,
            ]
        );

        $stages = [
            ['sequence' => 1, 'name' => 'Supervisor review', 'approver_role' => 'Supervisor', 'permission' => 'treatment.review', 'required_approvals' => 1, 'is_final' => false],
            ['sequence' => 2, 'name' => 'Manager approval', 'approver_role' => 'Manager', 'permission' => 'treatment.approve', 'required_approvals' => 1, 'is_final' => true],
        ];

        foreach ($stages as $stageDefinition) {
            $stage = $workflow->stages()
                ->where('sequence', $stageDefinition['sequence'])
                ->first();

            if (! $stage) {
                $workflow->stages()->create($stageDefinition);

                continue;
            }

            if ($stage->records()->exists() || $stage->treatmentApprovals()->exists()) {
                continue;
            }

            $stage->fill($stageDefinition)->save();
        }

        $types = [
            ['code' => 'write_off', 'name' => 'Write-off', 'description' => 'Remove an irrecoverable balance from the active receivable.', 'required_document_types' => ['approval_memo', 'supporting_evidence'], 'sort_order' => 1],
            ['code' => 'credit_adjustment', 'name' => 'Credit adjustment', 'description' => 'Apply a credit correction to a customer account.', 'required_document_types' => ['approval_memo'], 'sort_order' => 2],
            ['code' => 'refund', 'name' => 'Refund instruction', 'description' => 'Authorise a refund to a payer.', 'required_document_types' => ['approval_memo', 'payment_proof'], 'sort_order' => 3],
            ['code' => 'waiver', 'name' => 'Penalty waiver', 'description' => 'Waive a penalty or charge on an account.', 'required_document_types' => ['approval_memo'], 'sort_order' => 4],
            ['code' => 'transfer', 'name' => 'Account transfer', 'description' => 'Transfer a balance between accounts.', 'required_document_types' => [], 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            $model = AccountTreatmentType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type + [
                    'requires_approval' => true,
                    'approval_workflow_id' => $workflow->id,
                    'is_active' => true,
                ]
            );

            $reasons = match ($type['code']) {
                'write_off' => [
                    ['code' => 'uncollectible', 'name' => 'Confirmed uncollectible'],
                    ['code' => 'statute_barred', 'name' => 'Statute barred'],
                ],
                'credit_adjustment' => [
                    ['code' => 'overpayment', 'name' => 'Customer overpayment'],
                    ['code' => 'system_error', 'name' => 'System posting error'],
                ],
                'refund' => [
                    ['code' => 'duplicate_payment', 'name' => 'Duplicate payment'],
                    ['code' => 'service_not_rendered', 'name' => 'Service not rendered'],
                ],
                'waiver' => [
                    ['code' => 'hardship', 'name' => 'Financial hardship'],
                    ['code' => 'management_discount', 'name' => 'Management-approved discount'],
                ],
                default => [
                    ['code' => 'account_correction', 'name' => 'Account correction'],
                ],
            };

            foreach ($reasons as $reason) {
                $model->reasons()->updateOrCreate(['code' => $reason['code']], $reason + ['is_active' => true]);
            }
        }

        $customers = [
            ['reference' => 'CUS-0001', 'name' => 'Jos Marine Logistics Ltd', 'type' => 'corporate', 'email' => 'accounts@Josmarine.example', 'phone' => '+234 803 000 0001'],
            ['reference' => 'CUS-0002', 'name' => 'Jos Health Services', 'type' => 'corporate', 'email' => 'finance@Joshealth.example', 'phone' => '+234 803 000 0002'],
            ['reference' => 'CUS-0003', 'name' => 'Adebayo Ogunlesi', 'type' => 'individual', 'email' => 'adebayo@example.com', 'phone' => '+234 803 000 0003'],
            ['reference' => 'CUS-0004', 'name' => 'Rivers Agro Cooperative', 'type' => 'corporate', 'email' => 'coop@riversagro.example', 'phone' => '+234 803 000 0004'],
            ['reference' => 'CUS-0005', 'name' => 'Zainab Mohammed', 'type' => 'individual', 'email' => 'zainab@example.com', 'phone' => '+234 803 000 0005'],
        ];

        foreach ($customers as $customer) {
            $model = Customer::query()->updateOrCreate(['reference' => $customer['reference']], $customer + ['is_active' => true]);

            $accountBranch = match ($model->reference) {
                'CUS-0002' => $branchMap['JO'],
                'CUS-0004' => $branchMap['BA'],
                default => $branchMap['HQ'],
            };

            Account::query()->updateOrCreate(
                ['account_number' => 'ACC-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $model->id,
                    'branch_id' => $accountBranch,
                    'name' => $model->name,
                    'type' => 'customer',
                    'status' => 'active',
                ]
            );
        }

        $settings = [
            ['key' => 'organisation.name', 'group' => 'organisation', 'label' => 'Organisation name', 'type' => 'string', 'value' => 'Revenue Collection Office', 'is_public' => true],
            ['key' => 'organisation.short_name', 'group' => 'organisation', 'label' => 'Short name', 'type' => 'string', 'value' => 'Revenue Office', 'is_public' => true],
            ['key' => 'organisation.currency', 'group' => 'organisation', 'label' => 'Functional currency', 'type' => 'string', 'value' => 'NGN', 'is_public' => true],
            ['key' => 'organisation.timezone', 'group' => 'organisation', 'label' => 'Display timezone', 'type' => 'string', 'value' => 'UTC', 'is_public' => true],
            ['key' => 'payments.allow_future_date', 'group' => 'payments', 'label' => 'Allow future payment dates', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'payments.require_reversal_approval', 'group' => 'payments', 'label' => 'Require second approver for reversals', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'collections.require_separate_lodgement_approver', 'group' => 'collections', 'label' => 'Verifier cannot approve lodging', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'collections.variance_requires_notes', 'group' => 'collections', 'label' => 'Variance requires explanation', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'lodgements.require_document', 'group' => 'lodgements', 'label' => 'Evidence required to submit lodgement', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'lodgements.overdue_days', 'group' => 'lodgements', 'label' => 'Days before lodgement is overdue', 'type' => 'integer', 'value' => '3'],
            ['key' => 'treasury.variance_requires_notes', 'group' => 'treasury', 'label' => 'Treasury variance requires explanation', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'treatment.allow_self_approval', 'group' => 'treatment', 'label' => 'Allow self approval (policy blocks)', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'notifications.mail_enabled', 'group' => 'notifications', 'label' => 'Send email notifications', 'type' => 'boolean', 'value' => '0'],
            ['key' => 'exports.retention_days', 'group' => 'exports', 'label' => 'Export retention (days)', 'type' => 'integer', 'value' => '30'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting + ['description' => null]
            );
        }

        Setting::flush();
    }
}
