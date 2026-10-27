<?php

namespace App\Support;

final class PermissionRegistry
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Dashboards' => [
                'dashboard.executive' => 'Executive dashboard',
                'dashboard.cashier' => 'Cashier dashboard',
                'dashboard.clearing' => 'Clearing dashboard',
                'dashboard.bank' => 'Bank dashboard',
                'dashboard.treasury' => 'Treasury dashboard',
            ],
            'Payments' => [
                'payments.view' => 'View payments',
                'payments.create' => 'Record payments',
                'payments.cancel' => 'Cancel unbatched payments',
                'payments.reverse' => 'Request payment reversals',
                'payments.approve_reversal' => 'Approve payment reversals',
                'payments.cheque_clear' => 'Record cheque clearance',
                'receipts.issue' => 'Issue receipts',
                'receipts.reprint' => 'Reprint receipts',
            ],
            'Cashiering & clearing' => [
                'shifts.manage' => 'Open and close cashier shifts',
                'batches.view' => 'View collection batches',
                'batches.submit' => 'Create and submit collection batches',
                'batches.verify' => 'Verify collection batches',
                'batches.approve_lodgement' => 'Approve batches for lodgement',
                'batches.close' => 'Close reconciled batches',
            ],
            'Bank lodgement' => [
                'lodgements.view' => 'View bank lodgements',
                'lodgements.create' => 'Create bank lodgements',
                'lodgements.submit' => 'Submit lodgements to treasury',
                'lodgements.return' => 'Return lodgements',
            ],
            'Treasury' => [
                'treasury.view' => 'View treasury workspace',
                'treasury.confirm' => 'Confirm treasury verification',
            ],
            'Reconciliation' => [
                'reconciliation.view' => 'View reconciliation runs',
                'reconciliation.run' => 'Run reconciliations',
                'reconciliation.close' => 'Close reconciled records',
            ],
            'Discrepancies' => [
                'discrepancies.view' => 'View discrepancies',
                'discrepancies.manage' => 'Manage discrepancies',
                'discrepancies.escalate' => 'Escalate discrepancies',
            ],
            'Account treatment' => [
                'treatment.view' => 'View treatment requests',
                'treatment.create' => 'Create treatment requests',
                'treatment.review' => 'Review treatment requests',
                'treatment.approve' => 'Approve treatment requests',
                'treatment.complete' => 'Complete approved treatments',
                'treatment.manage' => 'Configure treatment types and reasons',
            ],
            'Reporting' => [
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
            ],
            'Administration' => [
                'admin.users' => 'Manage users',
                'admin.roles' => 'Manage roles and permissions',
                'admin.branches' => 'Manage branches and collection points',
                'admin.channels' => 'Manage payment channels',
                'admin.banks' => 'Manage banks',
                'admin.workflows' => 'Manage approval workflows',
                'admin.settings' => 'Manage system settings',
                'audit.view' => 'View audit trail',
                'access_all_branches' => 'Access all branches',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_merge(...array_map(
            fn (array $permissions) => array_keys($permissions),
            array_values(self::groups())
        ));
    }

    public static function label(string $permission): string
    {
        foreach (self::groups() as $permissions) {
            if (isset($permissions[$permission])) {
                return $permissions[$permission];
            }
        }

        return str($permission)->replace(['.', '_'], ' ')->headline()->toString();
    }
}
