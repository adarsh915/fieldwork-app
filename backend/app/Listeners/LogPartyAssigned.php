<?php

namespace App\Listeners;

use App\Events\PartyAssigned;
use App\Models\AuditLog;

class LogPartyAssigned
{
    public function handle(PartyAssigned $event): void
    {
        AuditLog::create([
            'event'          => 'party.assigned',
            'auditable_type' => get_class($event->party),
            'auditable_id'   => $event->party->id,
            'causer_id'      => $event->assignedBy->id,
            'old_values'     => $event->previousEmployee
                ? ['employee_id' => $event->previousEmployee->id, 'employee_name' => $event->previousEmployee->name]
                : null,
            'new_values'     => [
                'employee_id'   => $event->assignment->employee_id,
                'employee_name' => $event->assignment->employee->name ?? null,
                'assigned_at'   => $event->assignment->assigned_at?->toISOString(),
            ],
            'ip_address'     => request()->ip(),
        ]);
    }
}
