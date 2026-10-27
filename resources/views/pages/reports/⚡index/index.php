<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Reports')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }

    public function render()
    {
        return view('pages.reports.⚡index.index');
    }
};
