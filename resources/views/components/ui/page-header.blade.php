@props(['title', 'subtitle' => null, 'back' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-xs font-medium text-slate-500 transition hover:text-navy-700">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back
            </a>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-slate-950">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
