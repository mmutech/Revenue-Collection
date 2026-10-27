<?php

namespace App\Events;

use App\Models\CollectionDiscrepancy;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscrepancyRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CollectionDiscrepancy $discrepancy) {}
}
