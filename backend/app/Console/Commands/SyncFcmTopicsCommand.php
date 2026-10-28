<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Console\Command;

class SyncFcmTopicsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fcm:sync-topics';

    /**
     * The console command description.
     */
    protected $description = 'Subscribe all active user device FCM tokens to Firebase Topics (all_employees and field_workers)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting FCM topic synchronization...');

        $users = User::where('is_active', true)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            $this->warn('No active users with registered FCM tokens found.');
            return 0;
        }

        $allTokens = $users->pluck('fcm_token')->unique()->values()->toArray();
        $tokenCount = count($allTokens);
        $this->info("Found {$users->count()} active user(s) with {$tokenCount} unique device token(s).");

        // 1. Subscribe to all_employees
        $this->line("Subscribing tokens to '/topics/all_employees'...");
        $resAll = FirebasePushService::subscribeToTopic($allTokens, 'all_employees');
        if ($resAll['success']) {
            $this->info("✓ Successfully synced all tokens to 'all_employees' topic!");
        } else {
            $this->error("✗ Failed to sync 'all_employees': " . ($resAll['message'] ?? 'Unknown error'));
        }

        // 2. Subscribe field workers to field_workers topic
        $fieldWorkers = $users->filter(fn($u) => $u->hasAnyRole(['Field Worker', 'field_worker', 'Employee']));
        if ($fieldWorkers->isNotEmpty()) {
            $fwTokens = $fieldWorkers->pluck('fcm_token')->unique()->values()->toArray();
            $this->line("Subscribing " . count($fwTokens) . " token(s) to '/topics/field_workers'...");
            $resFw = FirebasePushService::subscribeToTopic($fwTokens, 'field_workers');
            if ($resFw['success']) {
                $this->info("✓ Successfully synced field worker tokens to 'field_workers' topic!");
            } else {
                $this->error("✗ Failed to sync 'field_workers': " . ($resFw['message'] ?? 'Unknown error'));
            }
        }

        $this->info('FCM topics sync completed.');
        return 0;
    }
}
