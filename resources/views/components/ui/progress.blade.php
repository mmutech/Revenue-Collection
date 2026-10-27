@props(['value' => 0, 'max' => 100, 'color' => 'emerald'])

@php
    $percent = $max > 0 ? min(100, max(0, ((float) $value / (float) $max) * 100)) : 0;
    $bar = match ($color) {
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500',
        'navy' => 'bg-navy-700',
        default => 'bg-emerald-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full {{ $bar }}" style="width: {{ number_format($percent, 2) }}%"></div>
</div>
