<div>
    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Choose a new password</h1>
    <p class="mt-1.5 text-sm text-slate-500">Set a strong password of at least 8 characters.</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <x-ui.input wire:model="email" name="email" label="Email address" type="email" icon="user-circle" required />
        <x-ui.input wire:model="password" name="password" label="New password" type="password" icon="lock" required />
        <x-ui.input wire:model="password_confirmation" name="password_confirmation" label="Confirm new password" type="password" icon="lock" required />
        <x-ui.button type="submit" class="w-full" size="lg">Reset password</x-ui.button>
    </form>
</div>
