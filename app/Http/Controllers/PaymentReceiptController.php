<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Services\AuditLogger;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentReceiptController extends Controller
{
    public function __invoke(Payment $payment): Response
    {
        Gate::authorize('view', $payment);

        $user = auth()->user();

        DB::transaction(function () use ($payment, $user): void {
            $receipt = PaymentReceipt::query()
                ->where('payment_id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();
            $wasPrinted = $receipt->printed_at !== null;
            $previousCount = $receipt->reprint_count;

            if ($wasPrinted) {
                Gate::forUser($user)->authorize('receipts.reprint');
            } else {
                abort_unless(
                    $user->can('receipts.issue') || $user->can('receipts.reprint'),
                    403,
                );
            }

            $receipt->printed_at = now();

            if ($wasPrinted) {
                $receipt->reprint_count++;
            }

            $receipt->save();

            AuditLogger::log(
                $wasPrinted ? 'receipt.reprinted' : 'receipt.printed',
                $receipt,
                ['reprint_count' => $previousCount],
                ['reprint_count' => $receipt->reprint_count],
                null,
                'Receipts',
                $user,
            );
        });

        return response()->view('payments.receipt', [
            'payment' => $payment->load(['branch', 'channel', 'cashier', 'receipt']),
        ]);
    }
}
