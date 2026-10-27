<?php

namespace App\Events;

use App\Models\ReconciliationRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReconciliationCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ReconciliationRun $run) {}
}
