<?php

use App\Models\ReportExport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')]
#[Title('Report exports')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.export'), 403);
    }

    public function render()
    {
        $user = auth()->user();
        $exports = ReportExport::query()
            ->with('user')
            ->when(! $user->can('audit.view'), fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(15);

        return view('pages.reports.⚡exports.exports', [
            'exports' => $exports,
        ]);
    }
};
