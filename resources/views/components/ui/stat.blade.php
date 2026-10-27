@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'color' => 'navy', 'trend' => null, 'trendUp' => true])

@php
    $iconClasses = match ($color) {
        'emerald', 'mint' => 'bg-emerald-50 text-emerald-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'violet', 'purple' => 'bg-violet-50 text-violet-600',
        'orange', 'amber' => 'bg-orange-50 text-orange-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'teal' => 'bg-teal-50 text-teal-600',
        default => 'bg-navy-50 text-navy-600',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card-surface relative overflow-hidden p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
            <p class="tabular mt-2 truncate text-[26px] font-semibold leading-none text-slate-950">{{ $value }}</p>
            @if ($hint)
                <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
    @if ($trend)
        <div class="mt-3 flex items-center gap-1.5 text-xs font-medium {{ $trendUp ? 'text-emerald-600' : 'text-rose-600' }}">
            <x-icon :name="$trendUp ? 'trending-up' : 'arrow-up-right'" class="h-3.5 w-3.5" :class="$trendUp ? '' : 'rotate-90'" />
            <span>{{ $trend }}</span>
        </div>
    @endif
</div>
