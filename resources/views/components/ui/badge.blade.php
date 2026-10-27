@props(['color' => 'slate', 'dot' => false])

@php
    $classes = match ($color) {
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'mint' => 'bg-mint-50 text-mint-700 ring-mint-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'navy' => 'bg-navy-50 text-navy-700 ring-navy-600/20',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {$classes}"]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $slot }}
</span>
