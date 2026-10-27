<?php

namespace App\Events;

use App\Models\AccountTreatmentRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TreatmentDecisionRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public AccountTreatmentRequest $request, public string $decision) {}
}
