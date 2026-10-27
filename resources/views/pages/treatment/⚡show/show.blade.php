<div class="space-y-6">
    <x-ui.page-header
        :title="'Treatment request '.$treatment->reference"
        subtitle="Request details and approval history."
        :back="route('treatment.index')"
    >
        <x-slot:actions>
            <x-ui.status-badge :status="$treatment->status" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="Request details" class="xl:col-span-2">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-500">Account</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->account?->account_number ?? $treatment->account?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Branch</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->branch?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Treatment type</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->type?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Reason</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->reason?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Amount</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->amount === null ? '—' : \App\Support\Money::format($treatment->amount, $treatment->currency) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Effective date</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->effective_date?->format('j M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Requested by</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->requester?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Assigned to</dt><dd class="mt-1 text-sm font-medium">{{ $treatment->assignee?->name ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $treatment->description }}</dd></div>
                @if ($treatment->decision_notes)
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Decision notes</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $treatment->decision_notes }}</dd></div>
                @endif
            </dl>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Approval history">
                @if ($treatment->approvals->isEmpty())
                    <p class="text-sm text-slate-500">No approval decisions have been recorded.</p>
                @else
                    <ol class="space-y-4">
                        @foreach ($treatment->approvals as $approval)
                            <li wire:key="approval-{{ $approval->id }}" class="border-l-2 border-slate-200 pl-3">
                                <p class="text-sm font-medium">{{ $approval->stage_name }} · {{ $approval->decision?->value ?? 'Pending' }}</p>
                                <p class="text-xs text-slate-500">{{ $approval->approver?->name ?? 'Unassigned' }} · {{ $approval->decided_at?->format('j M Y H:i') ?? '—' }}</p>
                                @if ($approval->comments)
                                    <p class="mt-1 text-xs text-slate-600">{{ $approval->comments }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>

            <x-ui.card title="Supporting documents">
                @if ($treatment->documents->isEmpty())
                    <p class="text-sm text-slate-500">No documents are attached.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($treatment->documents as $document)
                            <li wire:key="treatment-document-{{ $document->id }}" class="text-sm">
                                <span class="font-medium">{{ $document->original_name }}</span>
                                <span class="block text-xs text-slate-500">{{ $document->document_type }} · {{ $document->uploader?->name ?? 'Unknown uploader' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
