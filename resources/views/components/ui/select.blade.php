@props(['label' => null, 'name', 'id' => null, 'help' => null, 'required' => false, 'placeholder' => null])

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
    <select
        id="{{ $id }}"
        @if($hasError) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => 'input-base'.($hasError ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
    @if ($help)
        <p class="mt-1.5 text-[11px] text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[11px] font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
