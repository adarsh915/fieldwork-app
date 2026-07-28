<?php

namespace App\Listeners;

use App\Events\PartyUpdated;
use App\Models\AuditLog;

class LogPartyUpdated
{
    public function handle(PartyUpdated $event): void
    {
        AuditLog::create([
            'event'          => 'party.updated',
            'auditable_type' => get_class($event->party),
            'auditable_id'   => $event->party->id,
            'causer_id'      => $event->updatedBy->id,
            'old_values'     => $event->oldValues,
            'new_values'     => $event->newValues,
            'ip_address'     => request()->ip(),
        ]);
    }
}
