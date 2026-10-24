<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DayStart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RajTradersVisitsAndOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 100 visits with consultation history and 100 orders for Jitendra Dhakad on Raj traders.
     */
    public function run(): void
    {
        $user = User::where('email', 'like', 'jitendra%')->first();
        if (!$user) {
            $msg = "User Jitendra Dhakad not found!";
            $this->command?->error($msg);
            echo $msg . PHP_EOL;
            return;
        }

        $party = Customer::where('name', 'like', 'Raj traders%')->first();
        if (!$party) {
            $party = Customer::find(13);
        }
        if (!$party) {
            $msg = "Party 'Raj traders' not found!";
            $this->command?->error($msg);
            echo $msg . PHP_EOL;
            return;
        }

        $msg = "Seeding 100 visits & 100 orders for User: {$user->name} (ID: {$user->id}) on Party: {$party->name} (ID: {$party->id})...";
        $this->command?->info($msg);
        echo $msg . PHP_EOL;

        // Ensure active products have product units
        $this->ensureProductUnits();

        $productUnits = ProductUnit::with('product')->get();
        if ($productUnits->isEmpty()) {
            $msg = "No product units available to seed orders.";
            $this->command?->error($msg);
            echo $msg . PHP_EOL;
            return;
        }

        $consultationTopics = [
            "Catalog & Samples Shown - Discussed new seasonal product line. Owner showed strong interest in irrigation fittings.",
            "Stock & Rate Inquiry - Verified current warehouse stock of fertilizer bags and negotiated volume discount.",
            "Payment Follow-up - Followed up on outstanding balance. Owner issued part payment and scheduled next instalment.",
            "Order Booking - Reviewed urgent customer orders for upcoming planting season. Placed order for prompt dispatch.",
            "Monthly Routine Courtesy Visit - Inspected shelf placement, verified promotional banner, discussed local farmer inquiries.",
            "Pricing & Scheme Negotiation - Discussed competitor pricing and seasonal festive booking discount scheme.",
            "Product Demonstration - Demonstrated new bio-pesticide sprayer and liquid nutrient application methods.",
            "Warranty & Quality Review - Resolved minor query regarding last month's PVC pipe consignment. Dealer completely satisfied.",
            "Dealer Expansion Meeting - Explored adding Raj Traders to priority distributor tier for western regional supply.",
            "Monsoon Season Readiness - Checked stock buffer ahead of heavy rains. Recommended 20% advance stock increase.",
            "Credit Limit Review - Assessed payment track record and approved 30-day extended credit cycle for upcoming quarter.",
            "Customer Feedback Discussion - Reviewed positive feedback from local farmers using AMPL premium seeds."
        ];

        $purposes = [
            "Order Booking",
            "Payment Collection",
            "Routine Visit",
            "Stock Verification",
            "Product Demo",
            "Catalog Showcase",
            "Scheme Negotiation",
            "Issue Resolution"
        ];

        $transports = [
            "VRL Logistics",
            "Sharma Transport",
            "TCI Freight",
            "Jaipur Golden Transport",
            "Local Tempo Delivery",
            "Indore Cargo Express"
        ];

        $orderTypes = ["Standard Order", "Bulk Booking", "Urgent Supply", "Seasonal Scheme", "Visit Report"];
        $paymentModes = ["Credit (30 Days)", "Advance RTGS", "Cheque", "Cash on Delivery", "Part Advance / Balance Credit"];
        $orderStatuses = ["delivered", "approved", "dispatched", "pending"];

        DB::beginTransaction();
        try {
            $createdVisits = 0;
            $createdOrders = 0;

            // Generate visits and orders across the past 120 days
            $startDate = Carbon::today()->subDays(120);

            for ($i = 1; $i <= 100; $i++) {
                // Spread dates evenly over 120 days with realistic daytime hours
                $dayOffset = (int) (($i * 1.18) % 118);
                $visitDate = (clone $startDate)->addDays($dayOffset);
                $hour = 10 + ($i % 8); // Between 10 AM and 5 PM
                $minute = ($i * 7) % 60;
                $checkInTime = (clone $visitDate)->setTime($hour, $minute, 0);
                $durationMinutes = 20 + (($i * 13) % 40); // 20 to 59 minutes duration
                $checkOutTime = (clone $checkInTime)->addMinutes($durationMinutes);

                // Find or create a DayStart for that date so the visit is valid
                $dayStart = DayStart::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'date'    => $visitDate->toDateString(),
                    ],
                    [
                        'tour_purpose_id' => 1,
                        'tour_type_id'    => 1,
                        'vehicle_type_id' => 1,
                        'vehicle_no'      => 'MP-09-AB-1234',
                        'opening_km'      => 12000 + ($dayOffset * 35),
                        'closing_km'      => 12000 + ($dayOffset * 35) + 65,
                        'start_time'      => (clone $visitDate)->setTime(9, 30, 0)->toTimeString(),
                        'end_time'        => (clone $visitDate)->setTime(18, 45, 0)->toTimeString(),
                        'status'          => 'approved',
                    ]
                );

                $noteTemplate = $consultationTopics[$i % count($consultationTopics)];
                $detailedNotes = "{$noteTemplate}\n• Next follow-up scheduled for next fortnight.\n• Discussed with owner Raj Yadav.";

                $visit = Visit::create([
                    'user_id'        => $user->id,
                    'party_id'       => $party->id,
                    'day_start_id'   => $dayStart->id,
                    'purpose'        => $purposes[$i % count($purposes)],
                    'check_in_time'  => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'check_in_at'    => $checkInTime,
                    'check_out_at'   => $checkOutTime,
                    'check_in_lat'   => '23.1815086',
                    'check_in_lng'   => '79.9263235',
                    'check_out_lat'  => '23.1812705',
                    'check_out_lng'  => '79.9262492',
                    'notes'          => $detailedNotes,
                    'remarks'        => $purposes[$i % count($purposes)],
                    'status'         => 'completed',
                    'created_at'     => $checkInTime,
                    'updated_at'     => $checkOutTime,
                ]);

                $createdVisits++;

                // Create Order linked to this visit
                $orderNo = sprintf('ORD-RAJ-%s-%04d', $visitDate->format('Ymd'), $i);
                $orderStatus = $orderStatuses[$i % count($orderStatuses)];

                $order = Order::create([
                    'order_no'             => $orderNo,
                    'user_id'              => $user->id,
                    'visit_id'             => $visit->id,
                    'party_id'             => $party->id,
                    'order_source'         => ($i % 5 === 0) ? 'phone' : 'visit',
                    'is_after_day_end'     => false,
                    'order_date'           => $visitDate->toDateString(),
                    'delivery_destination' => 'Raj Traders Godown, Station Road',
                    'preferred_transport'  => $transports[$i % count($transports)],
                    'vehicle_no'           => sprintf('MP-%02d-AB-%04d', ($i % 45) + 1, ($i * 73) % 9000 + 1000),
                    'dispatch_date'        => (clone $visitDate)->addDays(2 + ($i % 4))->toDateString(),
                    'remarks'              => "Order #{$i} booked for Raj Traders during routine visit.",
                    'schema'               => ($i % 3 === 0) ? 'Special Seasonal 3% Cash Discount' : 'Standard Trade Scheme',
                    'order_type'           => $orderTypes[$i % count($orderTypes)],
                    'payment_mode'         => $paymentModes[$i % count($paymentModes)],
                    'total_amount'         => 0.00,
                    'status'               => $orderStatus,
                    'created_at'           => (clone $checkInTime)->addMinutes(15),
                    'updated_at'           => (clone $checkInTime)->addMinutes(15),
                ]);

                // Create 1 to 3 items per order
                $itemCount = 1 + ($i % 3);
                $orderTotal = 0.00;

                for ($j = 0; $j < $itemCount; $j++) {
                    $unitIndex = ($i + $j) % $productUnits->count();
                    $unit = $productUnits[$unitIndex];
                    $qty = 2 + (($i + $j * 3) % 15);
                    $rate = (float) ($unit->price && $unit->price > 0 ? $unit->price : (250 + (($unitIndex + 1) * 150)));
                    $itemTotal = $qty * $rate;
                    $orderTotal += $itemTotal;

                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $unit->product_id,
                        'product_unit_id' => $unit->id,
                        'qty'             => $qty,
                        'rate'            => $rate,
                        'discount'        => 0.00,
                        'total'           => $itemTotal,
                        'created_at'      => $order->created_at,
                        'updated_at'      => $order->updated_at,
                    ]);
                }

                $order->update(['total_amount' => $orderTotal]);
                $createdOrders++;
            }

            DB::commit();

            $successMsg = "Successfully created {$createdVisits} visits and {$createdOrders} orders for Jitendra Dhakad on Raj traders!";
            $this->command?->info($successMsg);
            echo $successMsg . PHP_EOL;

        } catch (\Exception $e) {
            DB::rollBack();
            $errMsg = "Failed seeding visits & orders: " . $e->getMessage();
            $this->command?->error($errMsg);
            echo $errMsg . PHP_EOL;
        }
    }

    /**
     * Ensure all active products have at least one product unit.
     */
    private function ensureProductUnits(): void
    {
        $products = Product::where('status', 'active')->get();
        foreach ($products as $p) {
            if ($p->units()->count() === 0) {
                ProductUnit::create([
                    'product_id' => $p->id,
                    'unit_name'  => 'Standard Pack',
                    'price'      => 450.00,
                    'is_default' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            }
        }
    }
}
