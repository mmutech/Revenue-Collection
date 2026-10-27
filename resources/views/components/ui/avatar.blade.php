@props(['user', 'size' => 'md'])

@php
    $sizes = ['sm' => 'h-7 w-7 text-[10px]', 'md' => 'h-9 w-9 text-xs', 'lg' => 'h-12 w-12 text-sm'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <div class="flex {{ $sizes[$size] }} shrink-0 items-center justify-center rounded-full bg-navy-800 font-semibold text-white ring-2 ring-white">
        {{ $user->initials() }}
    </div>
    @if (! $slot->isEmpty())
        <div class="min-w-0">
            {{ $slot }}
        </div>
    @endif
</div>
