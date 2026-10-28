<?php

namespace Tests\Feature;

use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\FuelAllowanceRate;
use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\Trip;
use App\Models\User;
use App\Models\VehicleType;
use App\Services\FuelRateResolverService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FuelAllowanceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $salesRole;
    protected Role $managerRole;
    protected VehicleType $bike;
    protected VehicleType $car;
    protected FuelRateResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(FuelRateResolverService::class);

        // Create Roles
        $this->salesRole = Role::create(['name' => 'Sales Executive', 'guard_name' => 'web']);
        $this->managerRole = Role::create(['name' => 'Area Manager', 'guard_name' => 'web']);

        // Create User
        $this->user = User::factory()->create([
            'name' => 'John Fieldworker',
            'email' => 'john@example.com',
            'is_active' => true,
            'employee_id' => 'EMP-001',
        ]);
        $this->user->assignRole($this->salesRole);

        // Create Vehicle Types
        $this->bike = VehicleType::firstOrCreate(
            ['slug' => 'bike'],
            [
                'name' => 'Bike',
                'requires_vehicle_no' => true,
                'is_active' => true,
                'rate_per_km' => 3.50,
            ]
        );
        $this->bike->update(['rate_per_km' => 3.50, 'is_active' => true]);

        $this->car = VehicleType::firstOrCreate(
            ['slug' => 'car'],
            [
                'name' => 'Car',
                'requires_vehicle_no' => true,
                'is_active' => true,
                'rate_per_km' => 8.00,
            ]
        );
        $this->car->update(['rate_per_km' => 8.00, 'is_active' => true]);

        \App\Models\ExpenseCategory::firstOrCreate(
            ['name' => 'Travel'],
            ['is_active' => true]
        );
    }

    /** @test */
    public function it_resolves_global_base_rate_when_no_overrides_exist()
    {
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'scope_id' => null,
            'rate_per_km' => 3.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $resolved = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-10-08');

        $this->assertEquals(3.50, $resolved['rate_per_km']);
        $this->assertEquals('global_base', $resolved['source']);
        $this->assertEquals($this->bike->id, $resolved['vehicle_type_id']);
    }

    /** @test */
    public function it_resolves_role_rate_over_global_rate()
    {
        // Tier 3: Global
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'scope_id' => null,
            'rate_per_km' => 3.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Tier 2: Role
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'role',
            'scope_id' => $this->salesRole->id,
            'rate_per_km' => 4.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $resolved = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-10-08');

        $this->assertEquals(4.50, $resolved['rate_per_km']);
        $this->assertEquals('role_default', $resolved['source']);
    }

    /** @test */
    public function it_resolves_user_override_over_role_and_global_rates()
    {
        // Tier 3: Global
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'scope_id' => null,
            'rate_per_km' => 3.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Tier 2: Role
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'role',
            'scope_id' => $this->salesRole->id,
            'rate_per_km' => 4.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        // Tier 1: User Override
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'user',
            'scope_id' => $this->user->id,
            'rate_per_km' => 5.75,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $resolved = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-10-08');

        $this->assertEquals(5.75, $resolved['rate_per_km']);
        $this->assertEquals('user_override', $resolved['source']);
    }

    /** @test */
    public function it_safely_versions_rate_changes_without_corrupting_past_calculations()
    {
        // Old Rate: Active from Jan 1, 2026
        $oldRate = $this->resolver->setRate([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'rate_per_km' => 3.50,
            'effective_from' => '2026-01-01',
        ]);

        // Check calculation on 2026-05-15
        $pastCalc = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-05-15');
        $this->assertEquals(3.50, $pastCalc['rate_per_km']);

        // Admin increases rate to 4.00 starting from 2026-10-01
        $newRate = $this->resolver->setRate([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'rate_per_km' => 4.00,
            'effective_from' => '2026-10-01',
        ]);

        // 1. Verify old rate was archived automatically with effective_to = 2026-09-30
        $oldRate->refresh();
        $this->assertEquals('2026-09-30', $oldRate->effective_to->toDateString());

        // 2. Querying past date (2026-05-15) STILL resolves to 3.50 (zero retroactive corruption)
        $pastAgain = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-05-15');
        $this->assertEquals(3.50, $pastAgain['rate_per_km']);

        // 3. Querying new date (2026-10-08) resolves to 4.00
        $currentCalc = $this->resolver->getEffectiveRate($this->user->id, 'bike', '2026-10-08');
        $this->assertEquals(4.00, $currentCalc['rate_per_km']);
    }

    /** @test */
    public function it_freezes_snapshot_rates_on_trip_completion_and_never_modifies_them()
    {
        FuelAllowanceRate::create([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'rate_per_km' => 3.50,
            'effective_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $tourPurpose = TourPurpose::create(['name' => 'Client Visit', 'requires_party' => false, 'is_active' => true]);
        $tourType = TourType::create(['name' => 'HQ Tour', 'requires_vehicle' => true, 'is_active' => true]);

        $today = Carbon::today()->toDateString();

        $dayStart = DayStart::create([
            'user_id' => $this->user->id,
            'date' => $today,
            'tour_purpose_id' => $tourPurpose->id,
            'tour_type_id' => $tourType->id,
            'vehicle_type_id' => $this->bike->id,
            'vehicle_no' => 'MH-12-AB-1234',
            'opening_km' => 100.00,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->user);

        // End day with closing_km = 150 (50km travelled)
        $response = $this->postJson('/api/day/end', [
            'closing_km' => 150.00,
            'remarks' => 'Finished meetings',
        ]);

        $response->assertStatus(200);

        // Verify day_start snapshot
        $dayStart->refresh();
        $this->assertEquals(3.50, (float) $dayStart->applied_rate_per_km);
        $this->assertEquals('global_base', $dayStart->applied_rate_source);
        $this->assertEquals(175.00, (float) $dayStart->total_amount); // 50km * 3.50 = 175.00

        // Verify Trip record created with snapshot
        $trip = Trip::where('day_start_id', $dayStart->id)->first();
        $this->assertNotNull($trip);
        $this->assertEquals(50.00, (float) $trip->distance_km);
        $this->assertEquals(3.50, (float) $trip->applied_rate_per_km);
        $this->assertEquals(175.00, (float) $trip->total_amount);

        // Verify System-Generated ExpenseBill
        $expense = ExpenseBill::where('day_start_id', $dayStart->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(175.00, (float) $expense->amount);

        // Now Admin changes global rate to 10.00
        $this->resolver->setRate([
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'rate_per_km' => 10.00,
            'effective_from' => '2026-10-09',
        ]);

        // Re-read day_start and trip: must remain exactly 175.00 / 3.50
        $dayStart->refresh();
        $trip->refresh();
        $expense->refresh();

        $this->assertEquals(3.50, (float) $dayStart->applied_rate_per_km);
        $this->assertEquals(175.00, (float) $dayStart->total_amount);
        $this->assertEquals(175.00, (float) $trip->total_amount);
        $this->assertEquals(175.00, (float) $expense->amount);
    }

    /** @test */
    public function admin_can_manage_rates_via_api_endpoints()
    {
        $admin = User::factory()->create(['name' => 'Admin User', 'is_active' => true]);
        Sanctum::actingAs($admin);

        // 1. POST /api/admin/fuel-rates - Create Global Base Rate
        $resStore = $this->postJson('/api/admin/fuel-rates', [
            'vehicle_type_id' => $this->bike->id,
            'scope_type' => 'global',
            'rate_per_km' => 4.25,
            'effective_from' => '2026-01-01',
            'notes' => 'New standard rate',
        ]);

        $resStore->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rate_per_km', '4.25');

        $rateId = $resStore->json('data.id');

        // 2. GET /api/admin/fuel-rates
        $resIndex = $this->getJson('/api/admin/fuel-rates');
        $resIndex->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'vehicle_types',
                    'global_rates',
                    'role_rates',
                    'user_overrides',
                    'available_roles',
                    'available_users',
                ],
            ]);

        // 3. POST /api/admin/fuel-rates/resolve
        $resResolve = $this->postJson('/api/admin/fuel-rates/resolve', [
            'user_id' => $this->user->id,
            'vehicle_identifier' => 'bike',
            'distance_km' => 20,
        ]);

        $resResolve->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rate_per_km', 4.25)
            ->assertJsonPath('data.total_amount', 85);

        // 4. DELETE /api/admin/fuel-rates/{id}
        $resDelete = $this->deleteJson("/api/admin/fuel-rates/{$rateId}");
        $resDelete->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fuel_allowance_rates', ['id' => $rateId]);
    }
}
