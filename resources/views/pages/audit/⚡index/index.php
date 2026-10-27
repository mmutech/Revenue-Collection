<?php

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Audit trail')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('audit.view');
    }

    public function render(): View
    {
        return view('pages.audit.⚡index.index', [
            'entries' => AuditLog::query()
                ->with('user')
                ->latest('created_at')
                ->paginate(30),
        ]);
    }
};
