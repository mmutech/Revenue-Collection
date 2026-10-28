<div>
    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Sign in</h1>
    <p class="mt-1.5 text-sm text-slate-500">Use your corporate credentials to access the revenue control platform.</p>

    <form wire:submit="authenticate" class="mt-8 space-y-4">
        <x-ui.input
            wire:model="email"
            name="email"
            label="Email address"
            type="email"
            icon="user-circle"
            autocomplete="username"
            placeholder="name@organisation.com"
            required
            autofocus
        />

        <x-ui.input
            wire:model="password"
            name="password"
            label="Password"
            type="password"
            icon="lock"
            autocomplete="current-password"
            placeholder="••••••••"
            required
        />

        <div class="flex items-center justify-between pt-1">
            <x-ui.checkbox wire:model="remember" name="remember" label="Keep me signed in" />
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">Forgot password?</a>
        </div>

        <x-ui.button type="submit" class="w-full" size="lg" wire:loading.attr="disabled" wire:target="authenticate">
            <span wire:loading.remove wire:target="authenticate">Sign in securely</span>
            <span wire:loading wire:target="authenticate">Signing in…</span>
        </x-ui.button>
    </form>
</div>
