<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

final class Navigation
{
    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string, permission: string|null, exact?: bool}>}>
     */
    public static function groups(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        $groups = [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'permission' => null, 'exact' => true],
                ],
            ],
            [
                'label' => 'Collections',
                'items' => [
                    ['label' => 'Payments', 'route' => 'payments.index', 'icon' => 'banknotes', 'permission' => 'payments.view'],
                    ['label' => 'Cashier shifts', 'route' => 'shifts.index', 'icon' => 'clock', 'permission' => 'shifts.manage'],
                    ['label' => 'Daily batches', 'route' => 'collections.index', 'icon' => 'clipboard', 'permission' => 'batches.view'],
                    ['label' => 'Clearing queue', 'route' => 'collections.queue', 'icon' => 'scale', 'permission' => 'batches.verify'],
                ],
            ],
            [
                'label' => 'Bank & treasury',
                'items' => [
                    ['label' => 'Bank lodgements', 'route' => 'lodgements.index', 'icon' => 'bank', 'permission' => 'lodgements.view'],
                    ['label' => 'Treasury workspace', 'route' => 'treasury.index', 'icon' => 'shield', 'permission' => 'treasury.view'],
                    ['label' => 'Reconciliation', 'route' => 'reconciliation.index', 'icon' => 'refresh', 'permission' => 'reconciliation.view'],
                    ['label' => 'Discrepancies', 'route' => 'discrepancies.index', 'icon' => 'alert', 'permission' => 'discrepancies.view'],
                ],
            ],
            [
                'label' => 'Account treatment',
                'items' => [
                    ['label' => 'Treatment requests', 'route' => 'treatment.index', 'icon' => 'adjustments', 'permission' => 'treatment.view'],
                ],
            ],
            [
                'label' => 'Insight',
                'items' => [
                    ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'chart', 'permission' => 'reports.view'],
                    ['label' => 'Report exports', 'route' => 'reports.exports', 'icon' => 'download', 'permission' => 'reports.export'],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    ['label' => 'Users', 'route' => 'admin.users', 'icon' => 'users', 'permission' => 'admin.users'],
                    ['label' => 'Branches', 'route' => 'admin.branches', 'icon' => 'building-library', 'permission' => 'admin.branches'],
                    ['label' => 'Reference data', 'route' => 'admin.reference', 'icon' => 'wallet', 'permission' => 'admin.channels'],
                    ['label' => 'Approval workflows', 'route' => 'admin.workflows', 'icon' => 'check-circle', 'permission' => 'admin.workflows'],
                    ['label' => 'Roles & permissions', 'route' => 'admin.roles', 'icon' => 'lock', 'permission' => 'admin.roles'],
                    ['label' => 'System settings', 'route' => 'admin.settings', 'icon' => 'settings', 'permission' => 'admin.settings'],
                    ['label' => 'Audit trail', 'route' => 'audit.index', 'icon' => 'search', 'permission' => 'audit.view'],
                ],
            ],
        ];

        return collect($groups)
            ->map(function (array $group) use ($user) {
                $group['items'] = array_values(array_filter(
                    $group['items'],
                    fn (array $item) => $item['permission'] === null
                        ? true
                        : $user->can($item['permission'])
                ));

                return $group;
            })
            ->filter(fn (array $group) => $group['items'] !== [] && Route::has($group['items'][0]['route']))
            ->values()
            ->all();
    }

    public static function isActive(array $item): bool
    {
        if (! Route::has($item['route'])) {
            return false;
        }

        if ($item['exact'] ?? false) {
            return request()->routeIs($item['route']);
        }

        return request()->routeIs($item['route'], $item['route'].'.*');
    }
}
