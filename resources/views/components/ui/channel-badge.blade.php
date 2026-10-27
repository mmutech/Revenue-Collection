@props(['channel'])

@php
    $code = $channel instanceof \App\Models\PaymentChannel ? $channel->code : (string) $channel;
    $enum = \App\Enums\PaymentChannelCode::tryFrom($code);
    $label = $enum?->shortLabel() ?? str($code)->headline()->toString();
    $color = $enum?->color() ?? 'slate';
    $icon = $enum?->icon() ?? 'wallet';
@endphp

<x-ui.badge :color="$color">
    <x-icon :name="$icon" class="h-3 w-3" />
    {{ $label }}
</x-ui.badge>
