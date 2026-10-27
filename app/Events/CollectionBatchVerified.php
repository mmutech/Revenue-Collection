<?php

namespace App\Events;

use App\Models\CollectionBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollectionBatchVerified
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CollectionBatch $batch, public bool $withVariance = false) {}
}
