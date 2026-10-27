<?php

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Notifications')] class extends Component
{
    use WithPagination;

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->whereKey($notificationId)->first();

        abort_unless($notification, 404);

        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render(): View
    {
        return view('pages.notifications.⚡index.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(25),
        ]);
    }
};
