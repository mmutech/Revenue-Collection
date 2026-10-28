<?php

use App\Exceptions\FinancialRuleViolation;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Services\PaymentService;
use App\Support\Setting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')]
#[Title('Record payment')]
class extends Component
{
    public string $channel_id = '';

    public string $branch_id = '';

    public string $payer_name = '';

    public string $payer_phone = '';

    public string $payer_email = '';

    public string $amount = '';

    public string $currency = 'NGN';

    public string $payment_date = '';

    public string $external_reference = '';

    public string $provider = '';

    public string $settlement_date = '';

    public string $bank_id = '';

    public string $bank_branch_id = '';

    public string $terminal_id = '';

    public string $cheque_number = '';

    public string $cheque_date = '';

    public string $cheque_drawer = '';

    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('create', Payment::class);

        $user = auth()->user();
        $this->branch_id = (string) ($user->branch_id ?? '');
        $this->currency = Setting::string('organisation.currency', 'NGN');
        $this->payment_date = today()->toDateString();

        $cash = PaymentChannel::query()->where('code', 'cash')->where('is_active', true)->first();
        $this->channel_id = (string) ($cash?->id ?? PaymentChannel::query()->where('is_active', true)->value('id') ?? '');
    }

    #[Computed]
    public function channel(): ?PaymentChannel
    {
        return $this->channel_id !== ''
            ? PaymentChannel::query()->find($this->channel_id)
            : null;
    }

    #[Computed]
    public function bankBranches()
    {
        return $this->bank_id !== ''
            ? BankBranch::query()->where('bank_id', $this->bank_id)->where('is_active', true)->orderBy('name')->get()
            : collect();
    }

    public function save(PaymentService $payments)
    {
        $validated = $this->validate([
            'channel_id' => ['required', 'integer', 'exists:payment_channels,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'payer_name' => ['required', 'string', 'max:180'],
            'payer_phone' => ['nullable', 'string', 'max:30'],
            'payer_email' => ['nullable', 'email', 'max:190'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:100'],
            'settlement_date' => ['nullable', 'date'],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'bank_branch_id' => ['nullable', 'integer', 'exists:bank_branches,id'],
            'terminal_id' => ['nullable', 'string', 'max:60'],
            'cheque_number' => ['nullable', 'string', 'max:40'],
            'cheque_date' => ['nullable', 'date'],
            'cheque_drawer' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated = array_map(
            fn ($value) => $value === '' ? null : $value,
            $validated
        );

        if (! auth()->user()->canAccessBranch((int) $this->branch_id)) {
            $this->addError('branch_id', 'You are not authorised for the selected branch.');

            return null;
        }

        $validated['branch_id'] = (int) $validated['branch_id'];
        $validated['channel_id'] = (int) $validated['channel_id'];

        try {
            $payment = $payments->create($validated, auth()->user());
        } catch (FinancialRuleViolation $exception) {
            $this->addError('form', $exception->getMessage());

            return null;
        }

        session()->flash('status', 'Payment '.$payment->reference.' recorded successfully.');

        return $this->redirectRoute('payments.show', $payment, navigate: false);
    }

    public function render()
    {
        $user = auth()->user();

        return view('pages.payments.⚡create.create', [
            'channels' => PaymentChannel::query()->active()->get(),
            'branches' => Branch::query()->whereIn('id', $user->accessibleBranchIds())->orderBy('name')->get(),
            'banks' => Bank::query()->where('is_active', true)->orderBy('name')->get(),
            'openShift' => $user->openShift(),
        ]);
    }
};
