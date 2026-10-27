<div>
    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Reset your password</h1>
    <p class="mt-1.5 text-sm text-slate-500">Enter your corporate email and we will send a secure reset link.</p>

    @if ($status)
        <x-ui.alert variant="success" class="mt-6">{{ $status }}</x-ui.alert>
    @endif

    <form wire:submit="send" class="mt-6 space-y-4">
        <x-ui.input wire:model="email" name="email" label="Email address" type="email" icon="user-circle" required autofocus />
        <x-ui.button type="submit" class="w-full" size="lg">Send reset link</x-ui.button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:text-navy-900">Back to sign in</a>
    </p>
</div>
