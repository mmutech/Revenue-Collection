<?php

use App\Enums\BatchStatus;
use App\Enums\LodgementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TreatmentStatus;
use App\Models\AccountTreatmentRequest;
use App\Models\BankLodgement;
use App\Models\CashierShift;
use App\Models\CollectionBatch;
use App\Models\CollectionDiscrepancy;
use App\Models\Payment;
use App\Support\Setting;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Dashboard')] class extends Component
{
    public function render()
    {
        $user = auth()->user();
        $startOfMonth = CarbonImmutable::today()->startOfMonth();
        $startOfTrend = CarbonImmutable::today()->subDays(13);

        $payments = Payment::query()->visibleTo($user);
        $collected = [PaymentStatus::Received->value, PaymentStatus::Verified->value];

        $todayTotal = (clone $payments)->whereDate('payment_date', today())->whereIn('status', $collected)->sum('amount');
        $todayCount = (clone $payments)->whereDate('payment_date', today())->whereIn('status', $collected)->count();
        $mtdTotal = (clone $payments)->whereBetween('payment_date', [$startOfMonth->toDateString(), today()->toDateString()])->whereIn('status', $collected)->sum('amount');
        $mtdCount = (clone $payments)->whereBetween('payment_date', [$startOfMonth->toDateString(), today()->toDateString()])->whereIn('status', $collected)->count();

        $trendRows = (clone $payments)
            ->whereBetween('payment_date', [$startOfTrend->toDateString(), today()->toDateString()])
            ->whereIn('status', $collected)
            ->groupBy('payment_date')
            ->selectRaw('payment_date, SUM(amount) as total')
            ->pluck('total', 'payment_date');

        $trend = [];
        for ($day = $startOfTrend; $day->lte(CarbonImmutable::today()); $day = $day->addDay()) {
            $key = $day->toDateString();
            $trend[] = [
                'label' => $day->format('j M'),
                'value' => round((float) ($trendRows[$key] ?? $trendRows[$key.' 00:00:00'] ?? 0), 2),
            ];
        }

        $channelRows = (clone $payments)
            ->whereBetween('payment_date', [$startOfMonth->toDateString(), today()->toDateString()])
            ->whereIn('status', $collected)
            ->join('payment_channels', 'payment_channels.id', '=', 'payments.channel_id')
            ->groupBy('payment_channels.code', 'payment_channels.name')
            ->selectRaw('payment_channels.code as code, payment_channels.name as name, SUM(payments.amount) as total')
            ->get();

        $channelMix = $channelRows->map(fn ($row) => [
            'label' => $row->name,
            'value' => round((float) $row->total, 2),
        ])->values()->all();

        $outstandingBatches = CollectionBatch::query()->visibleTo($user)
            ->whereIn('status', [
                BatchStatus::ApprovedForLodgement->value,
                BatchStatus::PartiallyLodged->value,
                BatchStatus::FullyLodged->value,
            ])
            ->get()
            ->sum(fn (CollectionBatch $batch) => (float) $batch->outstandingAmount());

        $pendingVerification = CollectionBatch::query()->visibleTo($user)
            ->whereIn('status', [BatchStatus::Submitted->value, BatchStatus::PendingVerification->value])
            ->count();

        $awaitingTreasury = BankLodgement::query()->visibleTo($user)
            ->where('status', LodgementStatus::UnderTreasuryReview->value)
            ->count();

        $openDiscrepancies = CollectionDiscrepancy::query()->visibleTo($user)->open()->count();
        $openTreatments = AccountTreatmentRequest::query()->visibleTo($user)
            ->whereIn('status', [
                TreatmentStatus::Submitted->value,
                TreatmentStatus::UnderReview->value,
                TreatmentStatus::Returned->value,
            ])
            ->count();
        $awaitingApproval = AccountTreatmentRequest::query()->visibleTo($user)
            ->where('status', TreatmentStatus::UnderReview->value)
            ->count();

        $openShift = CashierShift::query()
            ->visibleTo($user)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $recentPayments = Payment::query()->visibleTo($user)
            ->with(['channel', 'branch', 'cashier', 'receipt'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $verificationQueue = $user->can('batches.verify')
            ? CollectionBatch::query()->visibleTo($user)
                ->with(['cashier', 'branch'])
                ->whereIn('status', [BatchStatus::Submitted->value, BatchStatus::PendingVerification->value])
                ->orderBy('submitted_at')
                ->limit(5)
                ->get()
            : collect();

        $treasuryQueue = $user->can('treasury.confirm')
            ? BankLodgement::query()->visibleTo($user)
                ->with(['bank', 'branch', 'creator'])
                ->where('status', LodgementStatus::UnderTreasuryReview->value)
                ->orderBy('submitted_at')
                ->limit(5)
                ->get()
            : collect();

        $treatmentQueue = $user->can('treatment.review') || $user->can('treatment.approve')
            ? AccountTreatmentRequest::query()->visibleTo($user)
                ->with(['account', 'type', 'requester'])
                ->where('status', TreatmentStatus::UnderReview->value)
                ->orderBy('submitted_at')
                ->limit(5)
                ->get()
            : collect();

        $discrepancyList = $user->can('discrepancies.view')
            ? CollectionDiscrepancy::query()->visibleTo($user)
                ->open()
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $lodgeableBatches = $user->can('lodgements.create')
            ? CollectionBatch::query()->visibleTo($user)
                ->whereIn('status', [BatchStatus::ApprovedForLodgement->value, BatchStatus::PartiallyLodged->value])
                ->get()
                ->sum(fn (CollectionBatch $batch) => (float) $batch->outstandingAmount())
            : 0.0;

        $chart = [
            'type' => 'line',
            'data' => [
                'labels' => array_column($trend, 'label'),
                'datasets' => [[
                    'label' => 'Collections',
                    'data' => array_column($trend, 'value'),
                    'borderColor' => '#14a578',
                    'backgroundColor' => 'rgba(20, 165, 120, 0.10)',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                ]],
            ],
        ];

        $mixChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_column($channelMix, 'label'),
                'datasets' => [[
                    'data' => array_column($channelMix, 'value'),
                    'backgroundColor' => ['#263a61', '#4f7cc5', '#14a578', '#6d4aff', '#f59e0b'],
                    'borderWidth' => 0,
                    'hoverOffset' => 4,
                ]],
            ],
            'showLegend' => true,
        ];

        return view('pages.dashboard.⚡index.index', [
            'user' => $user,
            'todayTotal' => $todayTotal,
            'todayCount' => $todayCount,
            'mtdTotal' => $mtdTotal,
            'mtdCount' => $mtdCount,
            'outstandingBatches' => $outstandingBatches,
            'pendingVerification' => $pendingVerification,
            'awaitingTreasury' => $awaitingTreasury,
            'openDiscrepancies' => $openDiscrepancies,
            'openTreatments' => $openTreatments,
            'awaitingApproval' => $awaitingApproval,
            'openShift' => $openShift,
            'recentPayments' => $recentPayments,
            'verificationQueue' => $verificationQueue,
            'treasuryQueue' => $treasuryQueue,
            'treatmentQueue' => $treatmentQueue,
            'discrepancyList' => $discrepancyList,
            'lodgeableBatches' => $lodgeableBatches,
            'chart' => $chart,
            'mixChart' => $mixChart,
            'currency' => Setting::string('organisation.currency', 'NGN'),
        ]);
    }
};
