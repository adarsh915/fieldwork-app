<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $party,
        public readonly array    $oldValues,
        public readonly array    $newValues,
        public readonly User     $updatedBy,
    ) {}
}
