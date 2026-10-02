<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NewSchemaDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding dummy data for new CRM modules...');

        try {
            $employees = User::role('Fieldworker')->get();
        } catch (\Exception $e) {
            $employees = collect();
        }
        
        if ($employees->isEmpty()) {
            $employees = User::all();
        }
        
        $customers = Customer::take(50)->get();
        $products = Product::take(10)->get();

        if ($employees->isEmpty() || $customers->isEmpty() || $products->isEmpty()) {
            $this->command->error('Missing prerequisite data (employees, customers, or products). Cannot seed.');
            return;
        }

        foreach ($employees->take(10) as $employee) {
            $userId = $employee->id;
            
            // 1. GPS Tracking Logs (10 per employee)
            $gpsLogs = [];
            for ($i = 0; $i < 10; $i++) {
                $gpsLogs[] = [
                    'user_id' => $userId,
                    'latitude' => 26.9124 + (rand(-50, 50) / 1000),
                    'longitude' => 75.7873 + (rand(-50, 50) / 1000),
                    'recorded_at' => now()->subHours(rand(1, 48)),
                    'battery_level' => rand(10, 100),
                    'accuracy' => rand(5, 50),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('gps_tracking_logs')->insert($gpsLogs);

            // 2. Payments (5 per employee)
            $payments = [];
            $paymentModes = ['cash', 'upi', 'cheque', 'bank_transfer'];
            for ($i = 0; $i < 5; $i++) {
                $payments[] = [
                    'receipt_no' => 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)) . rand(10, 99) . $userId . $i,
                    'party_id' => $customers->random()->id,
                    'collected_by' => $userId,
                    'amount' => rand(1000, 50000),
                    'payment_mode' => $paymentModes[array_rand($paymentModes)],
                    'status' => 'confirmed',
                    'collection_datetime' => now()->subDays(rand(1, 30)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('payments')->insert($payments);

            // 3. Expense Bills (3 per employee)
            $expenses = [];
            $statuses = ['pending', 'approved', 'rejected'];
            for ($i = 0; $i < 3; $i++) {
                $expenses[] = [
                    'user_id' => $userId,
                    'category_id' => rand(1, 5), // Assuming some categories exist or can be null
                    'date' => now()->subDays(rand(1, 15))->toDateString(),
                    'amount' => rand(100, 2000),
                    'status' => $statuses[array_rand($statuses)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('expense_bills')->insert($expenses);

            // 4. Leaves (1 per employee)
            DB::table('leaves')->insert([
                'user_id' => $userId,
                'leave_type_id' => rand(1, 3),
                'from_date' => now()->addDays(rand(1, 10))->toDateString(),
                'to_date' => now()->addDays(rand(11, 15))->toDateString(),
                'reason' => 'Personal work / Medical',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Reminders (5 per employee)
            $reminders = [];
            for ($i = 0; $i < 5; $i++) {
                $reminders[] = [
                    'user_id' => $userId,
                    'party_id' => $customers->random()->id,
                    'title' => 'Follow up on payment',
                    'remind_at' => now()->addDays(rand(1, 7)),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('reminders')->insert($reminders);

            // 6. Activities (5 per employee)
            $activities = [];
            $actTypes = ['Call', 'Email', 'Meeting', 'Site Visit'];
            for ($i = 0; $i < 5; $i++) {
                $activities[] = [
                    'user_id' => $userId,
                    'party_id' => $customers->random()->id,
                    'activity_type' => $actTypes[array_rand($actTypes)],
                    'description' => 'Discussed new requirements',
                    'date' => now()->subDays(rand(1, 10))->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('activities')->insert($activities);

            // 7. Meetings (2 per employee)
            $meetings = [];
            for ($i = 0; $i < 2; $i++) {
                $meetings[] = [
                    'user_id' => $userId,
                    'title' => 'Monthly Sync / Client Pitch',
                    'date' => now()->addDays(rand(1, 14))->toDateString(),
                    'time' => '14:30:00',
                    'attendees' => 'John, Jane',
                    'location' => 'Client Office',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('meetings')->insert($meetings);

            // 8. Product Demos (3 per employee)
            $demos = [];
            for ($i = 0; $i < 3; $i++) {
                $demos[] = [
                    'user_id' => $userId,
                    'party_id' => $customers->random()->id,
                    'product_id' => $products->random()->id,
                    'date' => now()->subDays(rand(1, 20))->toDateString(),
                    'feedback' => 'Customer was impressed by the quality.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('product_demos')->insert($demos);

            // 9. Deals (5 per employee)
            $deals = [];
            $stages = ['new', 'negotiation', 'won', 'lost'];
            for ($i = 0; $i < 5; $i++) {
                $deals[] = [
                    'party_id' => $customers->random()->id,
                    'user_id' => $userId,
                    'deal_value' => rand(50000, 500000),
                    'stage' => $stages[array_rand($stages)],
                    'expected_close_date' => now()->addDays(rand(10, 60))->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('deals')->insert($deals);

            // 10. Reward Points & Redemptions (1 per employee)
            DB::table('reward_points')->insert([
                'user_id' => $userId,
                'points' => rand(100, 1000),
                'reason' => 'Target Achieved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('reward_redemptions')->insert([
                'user_id' => $userId,
                'points_used' => rand(50, 100),
                'item' => 'Amazon Gift Card',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('New CRM module dummy data seeded successfully!');
    }
}