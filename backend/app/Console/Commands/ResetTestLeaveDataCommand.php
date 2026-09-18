<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Console\Command;

class ResetTestLeaveDataCommand extends Command
{
    protected $signature = 'leave:reset-test-data {user_id?}';
    protected $description = 'Reset leave applications and balances to clean state for scratch testing';

    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            Leave::where('user_id', $userId)->delete();
            LeaveBalance::where('user_id', $userId)->update([
                'used'            => 0,
                'carried_forward' => 0,
            ]);
            $this->info("Cleaned all leave test data for User ID {$userId}.");
        } else {
            Leave::query()->delete();
            LeaveBalance::query()->update([
                'used'            => 0,
                'carried_forward' => 0,
            ]);
            $this->info('Cleaned all leave test applications & reset balances for all users to 0 used.');
        }

        // Sync fresh allocated quotas from Leave Types
        $year  = now()->year;
        $types = LeaveType::active()->get();
        $users = $userId ? User::where('id', $userId)->get() : User::where('is_active', true)->get();

        foreach ($types as $type) {
            foreach ($users as $user) {
                LeaveBalance::updateOrCreate(
                    [
                        'user_id'       => $user->id,
                        'leave_type_id' => $type->id,
                        'year'          => $year,
                    ],
                    [
                        'allocated'       => $type->accrual_frequency === 'monthly' ? $type->days_per_month : $type->days_per_year,
                        'used'            => 0,
                        'carried_forward' => 0,
                    ]
                );
            }
        }

        $this->info('Successfully re-synced clean initial balances for testing!');
        return Command::SUCCESS;
    }
}
