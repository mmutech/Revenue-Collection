@props(['status'])

@php
    if ($status instanceof \App\Enums\Concerns\WorkflowStatus) {
        $label = $status->label();
        $color = $status->color();
    } elseif ($status instanceof \BackedEnum) {
        $label = method_exists($status, 'label') ? $status->label() : str($status->value)->headline()->toString();
        $color = method_exists($status, 'color') ? $status->color() : 'slate';
    } else {
        $label = str((string) $status)->replace('_', ' ')->headline()->toString();
        $color = match ((string) $status) {
            'verified', 'approved', 'confirmed', 'completed', 'matched', 'cleared' => 'emerald',
            'returned', 'partially_lodged', 'pending', 'under_review' => 'amber',
            'rejected', 'failed', 'dishonoured', 'unmatched' => 'rose',
            'reconciled', 'closed' => 'teal',
            'under_treasury_review' => 'purple',
            'submitted', 'received' => 'blue',
            default => 'slate',
        };
    }
@endphp

<x-ui.badge :color="$color" dot>{{ $label }}</x-ui.badge>
