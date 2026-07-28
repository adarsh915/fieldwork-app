<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DayStart;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic volume data for the Party Profile page:
 * ~50 visits, ~120 orders, ~120 transactions per party (for first 20 parties).
 */
class PartyDetailSeeder extends Seeder
{
    public function run(): void
    {
        // Seed details for the first 20 parties that have a fieldworker assigned
        $parties = Customer::whereNotNull('user_id')->take(20)->get();

        if ($parties->isEmpty()) {
            $this->command->error('No parties with assigned fieldworkers found.');
            return;
        }

        $this->command->info("Seeding detailed data for {$parties->count()} parties...");

        $visitStatuses = ['completed', 'completed', 'completed', 'in_progress', 'completed'];
        $orderStatuses = ['delivered', 'delivered', 'pending', 'processing', 'cancelled'];
        $paymentModes  = ['cash', 'upi', 'cheque', 'bank_transfer', 'credit'];
        $txnTypes      = ['payment', 'payment', 'payment', 'refund', 'credit'];
        $txnStatuses   = ['completed', 'completed', 'completed', 'pending', 'failed'];
        $remarksList   = [
            'Good meeting, discussed new requirements.',
            'Collected pending payment.',
            'Delivered product samples.',
            'Customer requested callback next week.',
            'Met with owner, discussed bulk order.',
            'Quick follow-up visit.',
            'Demonstrated new product line.',
            'Resolved complaint about last delivery.',
            'Customer placed repeat order.',
            'Surveyed land area for new installation.',
        ];
        $notesList = [
            'Payment received in full.',
            'Partial payment, balance next month.',
            'Advance for upcoming order.',
            'Refund processed for damaged goods.',
            'Credit applied for loyalty discount.',
            'Cash collected on-site.',
            'Bank transfer confirmed.',
            'Cheque deposited, pending clearance.',
            'UPI payment received.',
            'Adjusted against previous credit note.',
        ];

        // ── Get master data IDs ──
        $tourPurposeId = \App\Models\TourPurpose::first()->id ?? 1;
        $tourTypeId    = \App\Models\TourType::first()->id ?? 1;
        $vehicleTypeId = \App\Models\VehicleType::first()->id ?? 1;

        foreach ($parties as $party) {
            $this->command->info("  → {$party->name} ...");
            $userId = $party->user_id;

            // ── Create DayStarts & Visits (~50 per party) ──
            $visitCount = rand(45, 55);
            $partyVisitIds = [];
            for ($v = 0; $v < $visitCount; $v++) {
                $date = now()->subDays(rand(1, 365));
                $checkIn = (clone $date)->setTime(rand(8, 16), rand(0, 59));
                $checkOut = (clone $checkIn)->addMinutes(rand(15, 120));

                while (DayStart::where('user_id', $userId)->where('date', $date->toDateString())->exists()) {
                    $date->subDays(1);
                }

                // Create a DayStart for this visit
                $dayStart = DayStart::create([
                    'user_id'         => $userId,
                    'date'            => $date->toDateString(),
                    'tour_purpose_id' => $tourPurposeId,
                    'tour_type_id'    => $tourTypeId,
                    'vehicle_type_id' => $vehicleTypeId,
                    'opening_km'      => rand(100, 500),
                    'start_latitude'  => $party->latitude,
                    'start_longitude' => $party->longitude,
                    'status'          => 'approved',
                ]);
                
                \App\Models\DayStartPlace::create([
                    'day_start_id' => $dayStart->id,
                    'place_name' => $party->address ?? 'Field Visit'
                ]);
                
                \App\Models\DayStartParty::create([
                    'day_start_id' => $dayStart->id,
                    'party_id' => $party->id,
                    'visit_status' => 'done'
                ]);

                $visit = Visit::create([
                    'user_id'       => $userId,
                    'day_start_id'  => $dayStart->id,
                    'party_id'      => $party->id,
                    'check_in_time'   => $checkIn,
                    'check_in_lat'  => $party->latitude ? $party->latitude + (rand(-100, 100) / 100000) : null,
                    'check_in_lng'  => $party->longitude ? $party->longitude + (rand(-100, 100) / 100000) : null,
                    'check_out_time'  => $checkOut,
                    'check_out_lat' => $party->latitude ? $party->latitude + (rand(-100, 100) / 100000) : null,
                    'check_out_lng' => $party->longitude ? $party->longitude + (rand(-100, 100) / 100000) : null,
                    'notes'       => $remarksList[array_rand($remarksList)],
                    'status'        => 'completed',
                ]);

                $partyVisitIds[] = $visit->id;
            }

            // ── Orders (~120 per party) ──
            $orders = [];
            for ($o = 0; $o < rand(100, 130); $o++) {
                $date = now()->subDays(rand(1, 365));
                $orders[] = [
                    'order_no'     => 'ORD-' . uniqid(),
                    'user_id'      => $userId,
                    'party_id'     => $party->id,
                    'visit_id'     => $partyVisitIds[array_rand($partyVisitIds)],
                    'order_date'   => $date,
                    'total_amount' => rand(500, 50000) + rand(0, 99) / 100,
                    'status'       => 'approved',
                    'remarks'      => $remarksList[array_rand($remarksList)],
                    'created_at'   => $date,
                    'updated_at'   => $date,
                ];
            }
            foreach (array_chunk($orders, 50) as $chunk) {
                Order::insert($chunk);
            }

            // ── Transactions (~120 per party) ──
            $transactions = [];
            for ($t = 0; $t < rand(100, 140); $t++) {
                $date = now()->subDays(rand(1, 365));
                $transactions[] = [
                    'party_id'    => $party->id,
                    'user_id'     => $userId,
                    'amount'      => rand(200, 40000) + rand(0, 99) / 100,
                    'type'        => 'credit',
                    'status'      => 'completed',
                    'notes'       => $notesList[array_rand($notesList)],
                    'created_at'  => $date,
                    'updated_at'  => $date,
                ];
            }
            foreach (array_chunk($transactions, 50) as $chunk) {
                Transaction::insert($chunk);
            }
        }

        $this->command->info('Party detail seeding completed!');
    }
}
