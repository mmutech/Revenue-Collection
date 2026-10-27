@props(['label' => null, 'name', 'id' => null, 'type' => 'text', 'help' => null, 'required' => false, 'icon' => null])

@php
    $id = $id ?? str_replace(['[', ']', '.'], ['_', '', '_'], $name);
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-xs font-semibold text-slate-700">
            {{ $label }}@if($required)<span class="ml-0.5 text-rose-500">*</span>@endif
        </label>
    @endif
    <div class="relative">
        @if ($icon)
            <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            @if($hasError) aria-invalid="true" @endif
            {{ $attributes->except('class')->merge(['class' => 'input-base'.($icon ? ' pl-9' : '').($hasError ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' : '')]) }}
        >
    </div>
    @if ($help)
        <p class="mt-1.5 text-[11px] text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
