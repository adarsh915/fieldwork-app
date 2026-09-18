<?php

namespace App\Console\Commands;

use App\Models\DayStart;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCloseDayStartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:auto-close-days';

    /**
     * The console command description.
     */
    protected $description = 'Find and auto-close unclosed DayStart records (where reps forgot to click Day End)';

    /**
     * Execute the console command.
     */
    public function handle(SettingsService $settings): int
    {
        $closeTime = $settings->get('auto_close_time', '23:59:59');

        $openRecords = DayStart::whereNull('end_time')
            ->where('date', '<=', Carbon::today()->toDateString())
            ->get();

        if ($openRecords->isEmpty()) {
            $this->info('No unclosed DayStart records found.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($openRecords as $record) {
            $record->update([
                'end_time'       => $closeTime,
                'is_auto_closed' => true,
                'flagged_reason' => 'System Auto-Closed: Missing Day End',
            ]);
            $count++;
        }

        $this->info("Successfully auto-closed {$count} DayStart record(s).");
        return Command::SUCCESS;
    }
}
