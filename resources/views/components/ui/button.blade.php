@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'icon' => null])

@php
    $variants = [
        'primary' => 'bg-navy-900 text-white hover:bg-navy-800 focus-visible:ring-navy-500',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-navy-500',
        'soft' => 'bg-navy-50 text-navy-700 hover:bg-navy-100 focus-visible:ring-navy-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-500 focus-visible:ring-rose-500',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-500 focus-visible:ring-emerald-500',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:ring-navy-500',
    ];
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-3.5 py-2 text-sm',
        'lg' => 'px-4 py-2.5 text-sm',
    ];
    $classes = 'btn-base '.$variants[$variant].' '.$sizes[$size];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
