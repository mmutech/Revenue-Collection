<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.guest')]
#[Title('Reset password')] class extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function send(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $result = Password::sendResetLink(['email' => $this->email]);

        $this->status = $result === Password::RESET_LINK_SENT
            ? 'If the address exists, a password reset link has been sent.'
            : 'We could not send a reset link for that address.';
    }

    public function render()
    {
        return view('pages.auth.⚡forgot-password.forgot-password');
    }
};
