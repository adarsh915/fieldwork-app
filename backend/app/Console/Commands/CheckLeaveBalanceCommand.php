<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Console\Command;

class CheckLeaveBalanceCommand extends Command
{
    protected $signature = 'check:leave-balance';
    protected $description = 'Inspect all leave balances and pending leaves in database';

    public function handle()
    {
        $this->info('--- LEAVE BALANCES ---');
        $balances = LeaveBalance::with(['user:id,name', 'leaveType:id,name,code,days_per_year,days_per_month'])->get();
        foreach ($balances as $b) {
            $this->line("ID: {$b->id} | User: {$b->user?->name} (ID {$b->user_id}) | Type: {$b->leaveType?->code} | Allocated: {$b->allocated} | CarryFwd: {$b->carried_forward} | Used: {$b->used} | Available: {$b->available}");
        }

        $this->newLine();
        $this->info('--- LEAVES APPLICATIONS ---');
        $leaves = Leave::with(['user:id,name', 'leaveType:id,name,code'])->get();
        foreach ($leaves as $l) {
            $this->line("ID: {$l->id} | User: {$l->user?->name} (ID {$l->user_id}) | Type: {$l->leaveType?->code} | Dates: {$l->from_date->format('Y-m-d')} to {$l->to_date->format('Y-m-d')} | Duration: {$l->duration} | DaysCount: {$l->days_count} | Status: {$l->status}");
        }

        return Command::SUCCESS;
    }
}
