<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChequeStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Payment $payment, public string $status) {}
}
