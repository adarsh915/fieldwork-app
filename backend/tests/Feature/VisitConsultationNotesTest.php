<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DayStart;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Tests\TestCase;

class VisitConsultationNotesTest extends TestCase
{
    public function test_visit_consultation_notes_can_be_updated_and_saved_on_checkout(): void
    {
        $user = User::first() ?? User::factory()->create();
        $party = Customer::first() ?? Customer::create(['name' => 'Test Party']);

        $dayStart = DayStart::where('user_id', $user->id)->whereDate('date', Carbon::today())->first();
        if (!$dayStart) {
            $dayStart = DayStart::create([
                'user_id' => $user->id,
                'date' => Carbon::today(),
                'opening_km' => 100,
                'tour_purpose_id' => \Illuminate\Support\Facades\DB::table('tour_purposes')->value('id') ?? 1,
                'tour_type_id' => \Illuminate\Support\Facades\DB::table('tour_types')->value('id') ?? 1,
                'vehicle_type_id' => \Illuminate\Support\Facades\DB::table('vehicle_types')->value('id') ?? 1,
                'vehicle_no' => 'DL01AB1234',
                'start_time' => Carbon::now(),
            ]);
        }

        $visit = Visit::create([
            'user_id' => $user->id,
            'day_start_id' => $dayStart->id,
            'party_id' => $party->id,
            'check_in_time' => Carbon::now(),
            'status' => 'pending',
        ]);

        $this->actingAs($user, 'sanctum');

        // 1. Update notes mid-visit
        $noteResponse = $this->patchJson("/api/visits/{$visit->id}/notes", [
            'notes' => 'Discussed new price catalog and sample book.',
        ]);

        $noteResponse->assertStatus(200);
        $noteResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'notes' => 'Discussed new price catalog and sample book.',
        ]);

        // 2. Checkout with final consultation notes
        $checkoutResponse = $this->patchJson("/api/visits/{$visit->id}/check-out", [
            'remarks' => 'Owner was busy today, promised to review catalog and place order next Tuesday.',
        ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => 'completed',
            'notes' => 'Owner was busy today, promised to review catalog and place order next Tuesday.',
        ]);

        // 3. Status returns recent_visits with consultation notes
        $statusResponse = $this->getJson("/api/visits/party/{$party->id}/status");
        $statusResponse->assertStatus(200);
        $statusResponse->assertJsonStructure([
            'success',
            'recent_visits',
        ]);
        $this->assertNotEmpty($statusResponse->json('recent_visits'));
    }
}
