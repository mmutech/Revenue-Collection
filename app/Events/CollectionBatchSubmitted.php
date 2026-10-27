<?php

namespace App\Events;

use App\Models\CollectionBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollectionBatchSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CollectionBatch $batch) {}
}
