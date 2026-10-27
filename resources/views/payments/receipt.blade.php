<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt->receipt_number }}</title>
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; margin: 2rem auto; max-width: 42rem; padding: 0 1rem; }
        h1 { font-size: 1.5rem; }
        dl { border-top: 1px solid #cbd5e1; display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; padding-top: 1rem; }
        dt { color: #64748b; font-size: .85rem; }
        dd { font-weight: 600; margin: 0; text-align: right; }
        .actions { margin-top: 2rem; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <h1>Payment receipt</h1>
    <p>{{ config('app.name') }}</p>
    <dl>
        <dt>Receipt number</dt><dd>{{ $payment->receipt->receipt_number }}</dd>
        <dt>Payment reference</dt><dd>{{ $payment->reference }}</dd>
        <dt>Payer</dt><dd>{{ $payment->payer_name }}</dd>
        <dt>Amount</dt><dd>{{ \App\Support\Money::format($payment->amount, $payment->currency) }}</dd>
        <dt>Payment date</dt><dd>{{ $payment->payment_date?->format('j F Y') }}</dd>
        <dt>Channel</dt><dd>{{ $payment->channel?->name ?? '—' }}</dd>
        <dt>Branch</dt><dd>{{ $payment->branch?->name ?? '—' }}</dd>
        <dt>Cashier</dt><dd>{{ $payment->cashier?->name ?? '—' }}</dd>
        <dt>Status</dt><dd>{{ $payment->status?->value ?? $payment->status }}</dd>
    </dl>
    <div class="actions">
        <button type="button" onclick="window.print()">Print receipt</button>
    </div>
</body>
</html>
