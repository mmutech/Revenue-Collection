@props(['amount' => 0, 'currency' => 'NGN', 'class' => ''])

<span {{ $attributes->merge(['class' => 'tabular '.$class]) }}>{{ \App\Support\Money::format($amount, $currency) }}</span>
