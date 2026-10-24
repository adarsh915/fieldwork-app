<?php

namespace Database\Seeders;

use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\ExpenseBillItem;
use App\Models\ExpenseCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JitendraExpensesSeeder extends Seeder
{
    public function run(): void
    {
        $jitendra = User::where('email', 'jitendra@gmail.com')->first();
        if (!$jitendra) {
            $this->command->error("User jitendra@gmail.com not found!");
            return;
        }

        $admin = User::role('Super Admin')->first() ?? User::find(1) ?? $jitendra;
        $categories = ExpenseCategory::all();
        if ($categories->isEmpty()) {
            $this->command->error("No expense categories found!");
            return;
        }

        $dayStarts = DayStart::where('user_id', $jitendra->id)->pluck('id')->toArray();

        $sampleItems = [
            'Food' => [
                ['name' => 'Team Lunch during field visit', 'price' => 450],
                ['name' => 'Tea & refreshments for client meeting', 'price' => 120],
                ['name' => 'Dinner on outstation tour', 'price' => 380],
                ['name' => 'Breakfast snack on highway', 'price' => 150],
            ],
            'Hotel' => [
                ['name' => 'Single AC Room 1 night', 'price' => 1600],
                ['name' => 'Standard Deluxe Stay', 'price' => 2200],
                ['name' => 'Budget Hotel stay for overnight audit', 'price' => 1200],
            ],
            'Travel' => [
                ['name' => 'Bus ticket to Indore', 'price' => 350],
                ['name' => 'Local Auto Rickshaw fare', 'price' => 180],
                ['name' => 'Toll plaza charges', 'price' => 210],
                ['name' => 'Train ticket 3rd AC', 'price' => 850],
            ],
            'Stationery' => [
                ['name' => 'Catalog Xerox & Binding', 'price' => 140],
                ['name' => 'Order receipt book and pens', 'price' => 95],
                ['name' => 'File folders for client contracts', 'price' => 210],
            ],
            'Misc' => [
                ['name' => 'Emergency mobile recharge during tour', 'price' => 299],
                ['name' => 'Vehicle puncture repair charges', 'price' => 80],
                ['name' => 'Courier charges for urgent invoice', 'price' => 120],
            ],
            'Other' => [
                ['name' => 'Client sample delivery handling', 'price' => 250],
                ['name' => 'Warehouse sample packing charges', 'price' => 175],
            ],
        ];

        $rejectionReasons = [
            'GST invoice is missing or illegible in the uploaded photo.',
            'Amount exceeds daily permissible allowance for this tier.',
            'Duplicate claim detected for the same date.',
            'Not approved as travel route deviated from scheduled plan without prior notice.',
            'Bill date does not match the active field tour date.',
        ];

        $statuses = [
            'pending' => 35,
            'temp_approved' => 20,
            'approved' => 30,
            'rejected' => 10,
            'manager_rejected' => 5,
        ];

        $createdCount = 0;
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            foreach ($statuses as $status => $targetCount) {
                for ($i = 0; $i < $targetCount; $i++) {
                    $category = $categories->random();
                    $catName = $category->name;
                    $itemsPool = $sampleItems[$catName] ?? $sampleItems['Misc'];

                    $numItems = rand(1, 3);
                    $selectedItems = [];
                    $totalAmount = 0;

                    for ($k = 0; $k < $numItems; $k++) {
                        $template = $itemsPool[array_rand($itemsPool)];
                        $qty = rand(1, 2);
                        $unitPrice = $template['price'] + rand(-20, 50);
                        if ($unitPrice < 50) $unitPrice = 50;
                        $lineTotal = $unitPrice * $qty;
                        $totalAmount += $lineTotal;

                        $selectedItems[] = [
                            'item_name' => $template['name'],
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'total_price' => $lineTotal,
                        ];
                    }

                    $daysAgo = rand(0, 45);
                    $expenseDate = $now->copy()->subDays($daysAgo)->format('Y-m-d');
                    $dayStartId = !empty($dayStarts) && rand(0, 10) > 2 ? $dayStarts[array_rand($dayStarts)] : null;

                    $tempApprovedBy = null;
                    $tempApprovedAt = null;
                    $tempApprovedNote = null;
                    $approvedBy = null;
                    $approvedAt = null;
                    $finalNote = null;
                    $rejectedBy = null;
                    $rejectedAt = null;
                    $rejectionReason = null;

                    if ($status === 'temp_approved') {
                        $tempApprovedBy = $admin->id;
                        $tempApprovedAt = $now->copy()->subDays(max(0, $daysAgo - 1));
                        $tempApprovedNote = 'Verified by L1 manager. Recommended for final finance clearance.';
                    } elseif ($status === 'approved') {
                        $tempApprovedBy = $admin->id;
                        $tempApprovedAt = $now->copy()->subDays(max(0, $daysAgo - 1));
                        $approvedBy = $admin->id;
                        $approvedAt = $now->copy()->subDays(max(0, $daysAgo - 2));
                        $finalNote = 'Approved and processed for monthly reimbursement.';
                    } elseif ($status === 'rejected' || $status === 'manager_rejected') {
                        $rejectedBy = $admin->id;
                        $rejectedAt = $now->copy()->subDays(max(0, $daysAgo - 1));
                        $rejectionReason = $rejectionReasons[array_rand($rejectionReasons)];
                    }

                    $isSystem = ($catName === 'Travel' && rand(1, 10) === 1);

                    $bill = ExpenseBill::create([
                        'user_id' => $jitendra->id,
                        'day_start_id' => $dayStartId,
                        'category_id' => $category->id,
                        'date' => $expenseDate,
                        'amount' => $totalAmount,
                        'bill_photo' => null,
                        'status' => $status,
                        'is_system_generated' => $isSystem,
                        'is_after_day_end' => rand(1, 10) === 1,
                        'temp_approved_by' => $tempApprovedBy,
                        'temp_approved_at' => $tempApprovedAt,
                        'temp_approved_note' => $tempApprovedNote,
                        'approved_by' => $approvedBy,
                        'approved_at' => $approvedAt,
                        'final_note' => $finalNote,
                        'rejected_by' => $rejectedBy,
                        'rejected_at' => $rejectedAt,
                        'rejection_reason' => $rejectionReason,
                        'created_at' => $now->copy()->subDays($daysAgo)->subHours(rand(1, 8)),
                        'updated_at' => $now->copy()->subDays($daysAgo),
                    ]);

                    foreach ($selectedItems as $item) {
                        ExpenseBillItem::create([
                            'expense_bill_id' => $bill->id,
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_price'],
                        ]);
                    }

                    $createdCount++;
                }
            }

            DB::commit();
            $this->command->info("Successfully seeded {$createdCount} expenses with items for Jitendra Dhakad.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding expenses: " . $e->getMessage());
            throw $e;
        }
    }
}
