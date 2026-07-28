<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\PartyEmployeeMap;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartyAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer         $party,
        public readonly PartyEmployeeMap $assignment,
        public readonly User             $assignedBy,
        public readonly ?User            $previousEmployee = null,
    ) {}
}
