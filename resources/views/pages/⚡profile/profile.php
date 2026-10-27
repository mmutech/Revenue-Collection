<?php

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('My profile')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $job_title = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->job_title = (string) $user->job_title;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ]);

        $user->fill($data)->save();
        AuditLogger::log('profile_updated', $user, [], $data, 'Profile details updated', 'Access', $user);

        $this->dispatch('toast', message: 'Profile updated.', type: 'success');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }

        $user->forceFill(['password' => Hash::make($this->password)])->save();
        AuditLogger::log('password_changed', $user, [], [], 'User changed their password', 'Access', $user);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('toast', message: 'Password changed successfully.', type: 'success');
    }

    public function render()
    {
        $user = auth()->user()->load(['branch', 'collectionPoint', 'roles']);

        return view('pages.⚡profile.profile', [
            'user' => $user,
            'recentActivity' => AuditLog::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->limit(12)
                ->get(),
        ]);
    }
};
