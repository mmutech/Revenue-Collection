<div>
    <x-ui.page-header title="My profile" subtitle="Manage your personal details and sign-in credentials." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Personal details" subtitle="Your name and contact information">
                <form wire:submit="saveProfile" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input wire:model="name" name="name" label="Full name" required />
                    <x-ui.input wire:model="email" name="email" type="email" label="Email address" required />
                    <x-ui.input wire:model="phone" name="phone" label="Phone" />
                    <x-ui.input wire:model="job_title" name="job_title" label="Job title" />
                    <div class="sm:col-span-2 flex justify-end">
                        <x-ui.button type="submit">Save changes</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Change password" subtitle="Use at least eight characters">
                <form wire:submit="updatePassword" class="space-y-4">
                    <x-ui.input wire:model="current_password" name="current_password" type="password" label="Current password" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="password" name="password" type="password" label="New password" required />
                        <x-ui.input wire:model="password_confirmation" name="password_confirmation" type="password" label="Confirm password" required />
                    </div>
                    <div class="flex justify-end">
                        <x-ui.button type="submit">Update password</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Recent activity" subtitle="Your latest audited actions" padding="false">
                @if ($recentActivity->isEmpty())
                    <x-ui.empty title="No activity yet" message="Actions you perform will appear here." icon="clock" />
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($recentActivity as $activity)
                            <div class="flex items-start gap-3 px-5 py-3">
                                <div class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700">{{ str($activity->event)->replace('_', ' ')->headline() }}</p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $activity->tags ?? $activity->auditable_type }}</p>
                                </div>
                                <time class="text-[11px] text-slate-400">{{ $activity->created_at?->diffForHumans() }}</time>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Access">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-navy-800 text-sm font-semibold text-white">{{ $user->initials() }}</div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->employee_no ?? 'No employee number' }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Roles</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->getRoleNames()->implode(', ') ?: 'None' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Branch</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->branch?->name ?? 'Group-wide' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Collection point</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->collectionPoint?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Last sign-in</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->last_login_at?->format('j M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.alert variant="info" title="Security notice">
                Never share your credentials. All financial approvals are attributed to your account and retained in the audit trail.
            </x-ui.alert>
        </div>
    </div>
</div>
