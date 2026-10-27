@props(['variant' => 'info', 'title' => null, 'dismissible' => false])

@php
    $styles = match ($variant) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'error' => 'border-rose-200 bg-rose-50 text-rose-800',
        default => 'border-blue-200 bg-blue-50 text-blue-800',
    };
    $icon = match ($variant) {
        'success' => 'check-circle',
        'warning' => 'alert',
        'error' => 'x-circle',
        default => 'information',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {$styles}"]) }} role="alert">
    <x-icon :name="$icon" class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
    </div>
</div>
