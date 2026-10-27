@props(['title' => null, 'subtitle' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'card-surface overflow-hidden']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                @if ($title)
                    <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
