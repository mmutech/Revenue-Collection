<?php

namespace App\Events;

use App\Models\BankLodgement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LodgementSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public BankLodgement $lodgement) {}
}
