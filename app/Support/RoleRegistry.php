<?php

namespace App\Support;

final class RoleRegistry
{
    public const ADMINISTRATOR = 'Administrator';

    public const CASHIER = 'Cashier';

    public const CLEARING_CASHIER = 'Clearing Cashier';

    public const BANK_OFFICER = 'Bank Officer';

    public const TREASURY_OFFICER = 'Treasury Officer';

    public const SUPERVISOR = 'Supervisor';

    public const MANAGER = 'Manager';

    public const AUDITOR = 'Auditor';

    /**
     * @return array<string, array{description: string, permissions: array<int, string>}>
     */
    public static function roles(): array
    {
        $viewEverything = [
            'payments.view',
            'batches.view',
            'lodgements.view',
            'treasury.view',
            'reconciliation.view',
            'discrepancies.view',
            'treatment.view',
            'reports.view',
        ];

        return [
            self::ADMINISTRATOR => [
                'description' => 'Full system administration and operational access across all branches.',
                'permissions' => PermissionRegistry::all(),
            ],
            self::CASHIER => [
                'description' => 'Captures payments, issues receipts, manages shifts and submits daily collections.',
                'permissions' => [
                    'dashboard.cashier',
                    'payments.view',
                    'payments.create',
                    'payments.cancel',
                    'receipts.issue',
                    'receipts.reprint',
                    'shifts.manage',
                    'batches.view',
                    'batches.submit',
                    'treatment.view',
                    'treatment.create',
                ],
            ],
            self::CLEARING_CASHIER => [
                'description' => 'Verifies cashier collections, records discrepancies and returns submissions.',
                'permissions' => [
                    'dashboard.clearing',
                    'payments.view',
                    'payments.cheque_clear',
                    'receipts.reprint',
                    'batches.view',
                    'batches.verify',
                    'discrepancies.view',
                    'discrepancies.manage',
                    'treatment.view',
                    'reports.view',
                ],
            ],
            self::BANK_OFFICER => [
                'description' => 'Creates bank lodgements, uploads evidence and submits them to treasury.',
                'permissions' => [
                    'dashboard.bank',
                    'payments.view',
                    'batches.view',
                    'lodgements.view',
                    'lodgements.create',
                    'lodgements.submit',
                    'reports.view',
                ],
            ],
            self::TREASURY_OFFICER => [
                'description' => 'Confirms deposits, reconciles treasury records and manages discrepancies.',
                'permissions' => [
                    'dashboard.treasury',
                    'payments.view',
                    'batches.view',
                    'lodgements.view',
                    'treasury.view',
                    'treasury.confirm',
                    'reconciliation.view',
                    'discrepancies.view',
                    'discrepancies.manage',
                    'discrepancies.escalate',
                    'treatment.view',
                    'reports.view',
                    'reports.export',
                ],
            ],
            self::SUPERVISOR => [
                'description' => 'Monitors assigned branches, approves lodgement and treatment exceptions.',
                'permissions' => array_merge($viewEverything, [
                    'dashboard.executive',
                    'batches.approve_lodgement',
                    'lodgements.return',
                    'discrepancies.manage',
                    'discrepancies.escalate',
                    'treatment.review',
                    'treatment.approve',
                    'reports.export',
                ]),
            ],
            self::MANAGER => [
                'description' => 'Group-wide oversight, exception approval, reconciliation close and audit review.',
                'permissions' => array_merge($viewEverything, [
                    'dashboard.executive',
                    'batches.approve_lodgement',
                    'batches.close',
                    'lodgements.return',
                    'discrepancies.manage',
                    'discrepancies.escalate',
                    'reconciliation.run',
                    'reconciliation.close',
                    'treatment.review',
                    'treatment.approve',
                    'treatment.complete',
                    'reports.export',
                    'audit.view',
                    'access_all_branches',
                ]),
            ],
            self::AUDITOR => [
                'description' => 'Read-only access to operational records, reports and the audit trail.',
                'permissions' => array_merge($viewEverything, [
                    'dashboard.executive',
                    'reports.export',
                    'audit.view',
                    'access_all_branches',
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function simpleList(): array
    {
        $roles = [];
        foreach (self::roles() as $name => $definition) {
            $roles[$name] = $definition['description'];
        }

        return $roles;
    }
}
