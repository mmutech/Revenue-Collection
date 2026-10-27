@props(['label', 'name', 'id' => null, 'help' => null])

@php
    $id = $id ?? str_replace(['[', ']', '.'], ['_', '', '_'], $name);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-2.5">
        <input
            id="{{ $id }}"
            type="checkbox"
            {{ $attributes->except('class')->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-slate-300 text-navy-700 focus:ring-navy-500']) }}
        >
        <span>
            <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
            @if ($help)
                <span class="mt-0.5 block text-xs text-slate-500">{{ $help }}</span>
            @endif
        </span>
    </label>
    @error($name)
        <p class="mt-1.5 text-[11px] font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
