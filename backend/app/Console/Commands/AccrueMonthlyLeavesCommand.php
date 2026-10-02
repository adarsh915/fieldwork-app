<?php

namespace App\Console\Commands;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrueMonthlyLeavesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leaves:accrue-monthly';

    /**
     * The console command description.
     */
    protected $description = 'Accrue monthly leave quotas for active employees based on monthly policy rules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = Carbon::now()->year;

        $monthlyTypes = LeaveType::active()
            ->where('accrual_frequency', 'monthly')
            ->where('days_per_month', '>', 0)
            ->get();

        if ($monthlyTypes->isEmpty()) {
            $this->info('No monthly accrual leave types configured.');
            return Command::SUCCESS;
        }

        $users = User::where('is_active', true)->pluck('id');
        $accruedCount = 0;

        foreach ($monthlyTypes as $type) {
            foreach ($users as $userId) {
                $balance = LeaveBalance::firstOrCreate(
                    [
                        'user_id'       => $userId,
                        'leave_type_id' => $type->id,
                        'year'          => $year,
                    ],
                    [
                        'allocated'       => 0,
                        'used'            => 0,
                        'carried_forward' => 0,
                    ]
                );

                if ($type->carry_forward) {
                    // Carry Forward = YES: Unused leaves roll over to next month (+days_per_month)
                    $balance->increment('allocated', $type->days_per_month);
                } else {
                    // Carry Forward = NO: Use-it-or-lose-it monthly. Unused leaves expire, reset available to days_per_month
                    $balance->update([
                        'allocated' => $balance->used + $type->days_per_month,
                    ]);
                }
                $accruedCount++;
            }
        }

        $this->info("Successfully accrued monthly leave quotas ({$accruedCount} balances updated).");
        return Command::SUCCESS;
    }
}
