<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestGpsTrackingSeeder extends Seeder
{
    /**
     * Seed GPS tracking data for testing route display
     * 
     * Creates a fake route simulating a field worker moving around
     */
    public function run(): void
    {
        // Get the first user ID (or change to your test user ID)
        $userId = DB::table('users')->where('email', '!=', 'admin@example.com')->first()->id ?? 1;
        
        $this->command->info("Creating GPS tracking route for User ID: {$userId}");
        
        // Starting time (today 9 AM)
        $startTime = Carbon::today()->setTime(9, 0, 0);
        
        // Simulated route - worker moving around Jabalpur area
        // These coordinates form a realistic movement path
        $route = [
            // Start at office
            ['lat' => 23.1813075, 'lng' => 79.9263911, 'time' => 0],   // 9:00 AM
            
            // Drive towards market
            ['lat' => 23.1825, 'lng' => 79.9275, 'time' => 5],         // 9:05 AM
            ['lat' => 23.1840, 'lng' => 79.9290, 'time' => 10],        // 9:10 AM
            ['lat' => 23.1860, 'lng' => 79.9310, 'time' => 15],        // 9:15 AM
            
            // Arrive at Customer 1
            ['lat' => 23.1885, 'lng' => 79.9335, 'time' => 20],        // 9:20 AM
            ['lat' => 23.1885, 'lng' => 79.9335, 'time' => 25],        // 9:25 AM (stationary)
            ['lat' => 23.1886, 'lng' => 79.9336, 'time' => 30],        // 9:30 AM (slight movement)
            
            // Move to Customer 2
            ['lat' => 23.1900, 'lng' => 79.9350, 'time' => 35],        // 9:35 AM
            ['lat' => 23.1920, 'lng' => 79.9370, 'time' => 40],        // 9:40 AM
            ['lat' => 23.1945, 'lng' => 79.9395, 'time' => 45],        // 9:45 AM
            
            // Arrive at Customer 2
            ['lat' => 23.1970, 'lng' => 79.9420, 'time' => 50],        // 9:50 AM
            ['lat' => 23.1971, 'lng' => 79.9421, 'time' => 55],        // 9:55 AM (stationary)
            ['lat' => 23.1970, 'lng' => 79.9420, 'time' => 60],        // 10:00 AM
            
            // Move to Customer 3
            ['lat' => 23.1985, 'lng' => 79.9445, 'time' => 65],        // 10:05 AM
            ['lat' => 23.2005, 'lng' => 79.9470, 'time' => 70],        // 10:10 AM
            ['lat' => 23.2030, 'lng' => 79.9500, 'time' => 75],        // 10:15 AM
            
            // Arrive at Customer 3
            ['lat' => 23.2055, 'lng' => 79.9530, 'time' => 80],        // 10:20 AM
            ['lat' => 23.2055, 'lng' => 79.9530, 'time' => 85],        // 10:25 AM
            
            // Return journey
            ['lat' => 23.2040, 'lng' => 79.9510, 'time' => 90],        // 10:30 AM
            ['lat' => 23.2020, 'lng' => 79.9480, 'time' => 95],        // 10:35 AM
            ['lat' => 23.1995, 'lng' => 79.9450, 'time' => 100],       // 10:40 AM
            ['lat' => 23.1970, 'lng' => 79.9420, 'time' => 105],       // 10:45 AM
            ['lat' => 23.1940, 'lng' => 79.9380, 'time' => 110],       // 10:50 AM
            ['lat' => 23.1910, 'lng' => 79.9340, 'time' => 115],       // 10:55 AM
            ['lat' => 23.1880, 'lng' => 79.9310, 'time' => 120],       // 11:00 AM
            ['lat' => 23.1850, 'lng' => 79.9280, 'time' => 125],       // 11:05 AM
            
            // Back at office
            ['lat' => 23.1813075, 'lng' => 79.9263911, 'time' => 130], // 11:10 AM
        ];
        
        // Clear existing test data for this user today
        DB::table('gps_tracking_logs')
            ->where('user_id', $userId)
            ->whereDate('recorded_at', Carbon::today())
            ->delete();
        
        $this->command->info("Cleared old GPS data for today");
        
        // Insert route points
        $insertCount = 0;
        foreach ($route as $point) {
            DB::table('gps_tracking_logs')->insert([
                'user_id' => $userId,
                'latitude' => $point['lat'],
                'longitude' => $point['lng'],
                'recorded_at' => $startTime->copy()->addMinutes($point['time']),
                'battery_level' => rand(60, 95),
                'accuracy' => rand(5, 20),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $insertCount++;
        }
        
        $this->command->info("✅ Inserted {$insertCount} GPS tracking points");
        $this->command->info("🗺️  Route spans approximately 4-5 km");
        $this->command->info("📍 View on map: http://192.168.1.22:8000/admin/tracking/live-map");
        $this->command->info("👤 User ID: {$userId}");
    }
}
