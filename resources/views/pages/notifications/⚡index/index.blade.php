<div class="space-y-6">
    <x-ui.page-header title="Notifications" subtitle="Updates and actions assigned to your account.">
        <x-slot:actions>
            <x-ui.button wire:click="markAllAsRead" variant="secondary" size="sm">Mark all as read</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card title="Your notifications" padding="false">
        @if ($notifications->isEmpty())
            <x-ui.empty title="No notifications" message="New updates will appear here." icon="bell" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <div wire:key="notification-{{ $notification->id }}" class="flex items-start justify-between gap-4 px-5 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/40' }}">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $notification->data['title'] ?? str(class_basename($notification->type))->headline() }}</p>
                            @if (! empty($notification->data['message']))
                                <p class="mt-1 text-sm text-slate-600">{{ $notification->data['message'] }}</p>
                            @endif
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        @if (! $notification->read_at)
                            <x-ui.button wire:click="markAsRead('{{ $notification->id }}')" variant="secondary" size="sm">Mark read</x-ui.button>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($notifications->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $notifications->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
