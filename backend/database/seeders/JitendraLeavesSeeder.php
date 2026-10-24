<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JitendraLeavesSeeder extends Seeder
{
    public function run(): void
    {
        $jitendra = User::where('email', 'jitendra@gmail.com')->first();
        if (!$jitendra) {
            $this->command->error("User jitendra@gmail.com not found!");
            return;
        }

        $admin = User::role('Super Admin')->first() ?? User::find(1) ?? $jitendra;
        $leaveTypes = LeaveType::all();
        if ($leaveTypes->isEmpty()) {
            $this->command->error("No leave types found!");
            return;
        }

        $reasons = [
            'Severe fever and cold, doctor advised bed rest.',
            'Attending family function / cousin wedding in native town.',
            'Urgent domestic work and bank document verification.',
            'Routine health checkup and dental treatment.',
            'Family emergency at hometown.',
            'Personal commitment and vehicle servicing.',
            'Festival celebration with parents.',
            'Viral infection recovery and medical review.',
            'Child school admission and parent-teacher meeting.',
            'House shifting and electrical maintenance.',
        ];

        $rejectionReasons = [
            'High volume of field tours scheduled on requested dates.',
            'Leave quota exhausted for this period.',
            'Another field executive is already on leave in this territory.',
            'Short notice application without manager pre-alignment.',
        ];

        $statuses = [
            'pending' => 25,
            'approved' => 45,
            'rejected' => 15,
            'cancellation_requested' => 10,
            'cancelled' => 5,
        ];

        $durations = ['full', 'full', 'full', 'half_am', 'half_pm'];

        DB::beginTransaction();
        try {
            $createdCount = 0;
            $baseDate = Carbon::now();

            // We generate past dates so they don't collide or lock future leave application dates
            $dayOffset = 1;

            foreach ($statuses as $status => $targetCount) {
                for ($i = 0; $i < $targetCount; $i++) {
                    $leaveType = $leaveTypes->random();
                    $duration = $durations[array_rand($durations)];
                    $reason = $reasons[array_rand($reasons)];

                    // Span of 1 to 3 days
                    $daysSpan = $duration === 'full' ? rand(1, 3) : 1;
                    $dayOffset += rand(2, 5);

                    $fromDate = $baseDate->copy()->subDays($dayOffset);
                    $toDate = $duration === 'full' && $daysSpan > 1 ? $fromDate->copy()->addDays($daysSpan - 1) : $fromDate->copy();

                    $approvedBy = null;
                    $approvedAt = null;
                    $rejectedReason = null;

                    if ($status === 'approved' || $status === 'cancellation_requested' || $status === 'cancelled') {
                        $approvedBy = $admin->id;
                        $approvedAt = $fromDate->copy()->subDay()->setTime(14, 30);
                    } elseif ($status === 'rejected') {
                        $approvedBy = $admin->id;
                        $rejectedReason = $rejectionReasons[array_rand($rejectionReasons)];
                    }

                    Leave::create([
                        'user_id' => $jitendra->id,
                        'leave_type_id' => $leaveType->id,
                        'from_date' => $fromDate->format('Y-m-d'),
                        'to_date' => $toDate->format('Y-m-d'),
                        'duration' => $duration,
                        'reason' => $reason,
                        'status' => $status,
                        'approved_by' => $approvedBy,
                        'approved_at' => $approvedAt,
                        'rejected_reason' => $rejectedReason,
                        'has_conflict' => false,
                        'conflict_status' => 'none',
                        'created_at' => $fromDate->copy()->subDays(2),
                        'updated_at' => $fromDate->copy()->subDay(),
                    ]);

                    $createdCount++;
                }
            }

            DB::commit();
            $this->command->info("Successfully seeded {$createdCount} leaves for Jitendra Dhakad.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding leaves: " . $e->getMessage());
            throw $e;
        }
    }
}
