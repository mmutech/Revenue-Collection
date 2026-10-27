@props(['title', 'message' => null, 'icon' => 'document'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-800">{{ $title }}</h3>
    @if ($message)
        <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-500">{{ $message }}</p>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
