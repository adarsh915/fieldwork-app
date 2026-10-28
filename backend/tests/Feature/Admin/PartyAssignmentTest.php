<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\PartyEmployeeMap;
use App\Models\AuditLog;
use App\Events\PartyAssigned;
use App\Events\PartyUpdated;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Create permissions
    Permission::create(['name' => 'customers.view']);
    Permission::create(['name' => 'customers.manage']);
    Permission::create(['name' => 'customers.create']);
    Permission::create(['name' => 'customers.edit']);
    Permission::create(['name' => 'customers.delete']);
    Permission::create(['name' => 'parties.assign']);

    // Create roles
    $this->superAdminRole = Role::create(['name' => 'Super Admin']);
    $this->managerRole = Role::create(['name' => 'Manager']);
    $this->fieldWorkerRole = Role::create(['name' => 'Field Worker']);

    // Assign permissions
    $this->superAdminRole->givePermissionTo(['customers.view', 'customers.manage', 'customers.create', 'customers.edit', 'customers.delete', 'parties.assign']);
    $this->managerRole->givePermissionTo(['customers.view', 'parties.assign']);
    
    // Create users
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('Super Admin');

    $this->manager = User::factory()->create();
    $this->manager->assignRole('Manager');

    $this->fieldWorker = User::factory()->fieldWorker()->create();
    $this->fieldWorker->assignRole('Field Worker');

    $this->inactiveFieldWorker = User::factory()->fieldWorker()->inactive()->create();
    $this->inactiveFieldWorker->assignRole('Field Worker');
});

// ─── PARTY INDEX & DATA TESTS ───────────────────────────────────────────────

test('authorized user can view party list page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.parties.index'))
        ->assertOk()
        ->assertViewIs('admin.parties.index')
        ->assertViewHas(['employees', 'partyTypes', 'categories']);
});

test('unauthorized user cannot view party list', function () {
    $unauthorizedUser = User::factory()->create();
    
    $this->actingAs($unauthorizedUser)
        ->getJson(route('admin.parties.index'))
        ->assertForbidden();
});

test('party datatable endpoint returns correct structure', function () {
    Customer::factory()->count(3)->create();

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'party_info',
                    'contact',
                    'classification',
                    'assignment',
                    'status',
                    'actions',
                ]
            ]
        ]);

    expect($response->json('data'))->toHaveCount(3);
});

test('party datatable filters by search term', function () {
    Customer::factory()->create(['name' => 'ABC Traders', 'party_code' => 'PTY001']);
    Customer::factory()->create(['name' => 'XYZ Corp', 'party_code' => 'PTY002']);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data', ['search' => 'ABC']))
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['party_info']['name'])->toBe('ABC Traders');
});

test('party datatable filters by employee assignment', function () {
    $employee = User::factory()->fieldWorker()->create();
    $employee->assignRole('Field Worker');

    $party1 = Customer::factory()->create();
    $party2 = Customer::factory()->create();

    PartyEmployeeMap::factory()->create([
        'party_id' => $party1->id,
        'employee_id' => $employee->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data', ['employee_id' => $employee->id]))
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['party_info']['id'])->toBe($party1->id);
});

test('party datatable filters by party type', function () {
    Customer::factory()->create(['party_type' => 'Retailer']);
    Customer::factory()->create(['party_type' => 'Distributor']);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data', ['party_type' => 'Retailer']))
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['classification']['type'])->toBe('Retailer');
});

test('party datatable filters by category', function () {
    Customer::factory()->create(['category' => 'A']);
    Customer::factory()->create(['category' => 'B']);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data', ['category' => 'A']))
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['classification']['category'])->toBe('A');
});

test('party datatable filters by status', function () {
    Customer::factory()->create(['is_active' => true]);
    Customer::factory()->inactive()->create();

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.data', ['status' => 'active']))
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['status'])->toBe('active');
});

// ─── PARTY UPDATE TESTS ─────────────────────────────────────────────────────

test('authorized user can update party details', function () {
    Event::fake([PartyUpdated::class]);

    $party = Customer::factory()->create([
        'name' => 'Old Name',
        'mobile' => '1234567890',
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->putJson(route('admin.parties.update', $party->id), [
            'party_code' => $party->party_code,
            'name' => 'New Name',
            'owner_name' => 'Owner Name',
            'mobile' => '9876543210',
            'email' => 'new@example.com',
            'address' => '123 New Street',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'party_type' => 'Retailer',
            'category' => 'A',
            'credit_limit' => 50000,
            'credit_days' => 30,
            'is_active' => true,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $party->refresh();
    expect($party->name)->toBe('New Name');
    expect($party->mobile)->toBe('9876543210');

    Event::assertDispatched(PartyUpdated::class);
});

test('unauthorized user cannot update party', function () {
    $party = Customer::factory()->create();
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser)
        ->putJson(route('admin.parties.update', $party->id), [
            'party_code' => $party->party_code,
            'name' => 'New Name',
        ])
        ->assertForbidden();
});

test('party update validates required fields', function () {
    $party = Customer::factory()->create();

    $this->actingAs($this->superAdmin)
        ->putJson(route('admin.parties.update', $party->id), [
            'name' => '', // Required field empty
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('party update validates party code uniqueness', function () {
    $party1 = Customer::factory()->create(['party_code' => 'PTY001']);
    $party2 = Customer::factory()->create(['party_code' => 'PTY002']);

    $this->actingAs($this->superAdmin)
        ->putJson(route('admin.parties.update', $party2->id), [
            'party_code' => 'PTY001', // Already exists
            'name' => $party2->name,
            'mobile' => '9876543210',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['party_code']);
});

test('party update creates audit log', function () {
    Event::fake([PartyUpdated::class]);

    $party = Customer::factory()->create(['name' => 'Old Name', 'mobile' => '9876543210']);

    $this->actingAs($this->superAdmin)
        ->putJson(route('admin.parties.update', $party->id), [
            'party_code' => $party->party_code,
            'name' => 'New Name',
            'mobile' => '9876543210',
            'is_active' => true,
        ]);

    Event::assertDispatched(PartyUpdated::class, function ($event) use ($party) {
        return $event->party->id === $party->id
            && $event->oldValues['name'] === 'Old Name'
            && $event->newValues['name'] === 'New Name';
    });
});

// ─── BULK ASSIGNMENT TESTS ──────────────────────────────────────────────────

test('authorized user can bulk assign parties to field worker', function () {
    Event::fake([PartyAssigned::class]);

    $parties = Customer::factory()->count(3)->create();
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');

    $response = $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => $parties->pluck('id')->toArray(),
            'employee_id' => $fieldWorker->id,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'assigned' => 3,
            'skipped' => 0,
        ]);

    // Verify assignments in database
    foreach ($parties as $party) {
        $this->assertDatabaseHas('party_employee_map', [
            'party_id' => $party->id,
            'employee_id' => $fieldWorker->id,
            'is_active' => true,
        ]);
    }

    Event::assertDispatched(PartyAssigned::class, 3);
});

test('unauthorized user cannot bulk assign parties', function () {
    $parties = Customer::factory()->count(2)->create();
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => $parties->pluck('id')->toArray(),
            'employee_id' => $fieldWorker->id,
        ])
        ->assertForbidden();
});

test('bulk assign validates employee is active field worker', function () {
    $parties = Customer::factory()->count(2)->create();

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => $parties->pluck('id')->toArray(),
            'employee_id' => $this->inactiveFieldWorker->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['employee_id']);
});

test('bulk assign skips already assigned parties', function () {
    Event::fake([PartyAssigned::class]);

    $party1 = Customer::factory()->create();
    $party2 = Customer::factory()->create();
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');

    // Pre-assign party1
    PartyEmployeeMap::create([
        'party_id' => $party1->id,
        'employee_id' => $fieldWorker->id,
        'assigned_by' => $this->superAdmin->id,
        'assigned_at' => now(),
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => [$party1->id, $party2->id],
            'employee_id' => $fieldWorker->id,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'assigned' => 1,
            'skipped' => 1,
        ]);

    Event::assertDispatched(PartyAssigned::class, 1);
});

test('bulk assign deactivates previous assignment when reassigning', function () {
    $party = Customer::factory()->create();
    $oldEmployee = User::factory()->fieldWorker()->create();
    $oldEmployee->assignRole('Field Worker');
    $newEmployee = User::factory()->fieldWorker()->create();
    $newEmployee->assignRole('Field Worker');

    // Create initial assignment
    $oldAssignment = PartyEmployeeMap::create([
        'party_id' => $party->id,
        'employee_id' => $oldEmployee->id,
        'assigned_by' => $this->superAdmin->id,
        'assigned_at' => now()->subDays(30),
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => [$party->id],
            'employee_id' => $newEmployee->id,
        ])
        ->assertOk();

    // Verify old assignment is deactivated
    $oldAssignment->refresh();
    expect($oldAssignment->is_active)->toBeFalse();
    expect($oldAssignment->unassigned_at)->not->toBeNull();

    // Verify new assignment is active
    $this->assertDatabaseHas('party_employee_map', [
        'party_id' => $party->id,
        'employee_id' => $newEmployee->id,
        'is_active' => true,
    ]);
});

test('bulk assign validates party_ids is required array', function () {
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'employee_id' => $fieldWorker->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['party_ids']);
});

test('bulk assign validates employee_id is required', function () {
    $parties = Customer::factory()->count(2)->create();

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => $parties->pluck('id')->toArray(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['employee_id']);
});

test('bulk assign records assignment metadata correctly', function () {
    $party = Customer::factory()->create();
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => [$party->id],
            'employee_id' => $fieldWorker->id,
        ]);

    $assignment = PartyEmployeeMap::where('party_id', $party->id)->first();

    expect($assignment->assigned_by)->toBe($this->superAdmin->id);
    expect($assignment->assigned_at)->not->toBeNull();
    expect($assignment->unassigned_at)->toBeNull();
    expect($assignment->is_active)->toBeTrue();
});

// ─── ASSIGNMENT HISTORY TESTS ───────────────────────────────────────────────

test('authorized user can view assignment history', function () {
    $party = Customer::factory()->create();
    $employee1 = User::factory()->fieldWorker()->create();
    $employee2 = User::factory()->fieldWorker()->create();

    // Create historical assignments
    PartyEmployeeMap::factory()->inactive()->create([
        'party_id' => $party->id,
        'employee_id' => $employee1->id,
        'assigned_by' => $this->superAdmin->id,
    ]);

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee2->id,
        'assigned_by' => $this->superAdmin->id,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.assignment-history', $party->id))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'party',
            'history' => [
                '*' => [
                    'employee',
                    'assigned_by',
                    'assigned_at',
                    'unassigned_at',
                    'is_active',
                    'duration_days',
                ]
            ]
        ]);

    expect($response->json('history'))->toHaveCount(2);
});

test('assignment history is ordered by most recent first', function () {
    $party = Customer::factory()->create();
    $employee1 = User::factory()->fieldWorker()->create();
    $employee2 = User::factory()->fieldWorker()->create();

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee1->id,
        'assigned_at' => now()->subDays(30),
    ]);

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee2->id,
        'assigned_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.assignment-history', $party->id));

    $history = $response->json('history');
    expect($history[0]['employee']['id'])->toBe($employee2->id);
    expect($history[1]['employee']['id'])->toBe($employee1->id);
});

test('assignment history calculates duration correctly', function () {
    $party = Customer::factory()->create();
    $employee = User::factory()->fieldWorker()->create();

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee->id,
        'assigned_at' => now()->subDays(30),
        'unassigned_at' => now()->subDays(10),
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.parties.assignment-history', $party->id));

    $history = $response->json('history');
    expect($history[0]['duration_days'])->toBe(20);
});

test('unauthorized user cannot view assignment history', function () {
    $party = Customer::factory()->create();
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser)
        ->getJson(route('admin.parties.assignment-history', $party->id))
        ->assertForbidden();
});

// ─── PARTY DELETE TESTS ─────────────────────────────────────────────────────

test('authorized user can delete party', function () {
    $party = Customer::factory()->create();

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.parties.destroy', $party->id))
        ->assertRedirect(route('admin.parties.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('customers', ['id' => $party->id]);
});

test('unauthorized user cannot delete party', function () {
    $party = Customer::factory()->create();
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser)
        ->deleteJson(route('admin.parties.destroy', $party->id))
        ->assertForbidden();

    $this->assertDatabaseHas('customers', ['id' => $party->id]);
});

// ─── EVENT & AUDIT LOG TESTS ────────────────────────────────────────────────

test('party assigned event is dispatched on assignment', function () {
    Event::fake([PartyAssigned::class]);

    $party = Customer::factory()->create();
    $fieldWorker = User::factory()->fieldWorker()->create();
    $fieldWorker->assignRole('Field Worker');

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.parties.bulk-assign'), [
            'party_ids' => [$party->id],
            'employee_id' => $fieldWorker->id,
        ]);

    Event::assertDispatched(PartyAssigned::class, function ($event) use ($party, $fieldWorker) {
        return $event->party->id === $party->id
            && $event->assignment->employee_id === $fieldWorker->id
            && $event->assignedBy->id === $this->superAdmin->id;
    });
});

test('party updated event is dispatched on update', function () {
    Event::fake([PartyUpdated::class]);

    $party = Customer::factory()->create(['name' => 'Old Name', 'mobile' => '9876543210']);

    $this->actingAs($this->superAdmin)
        ->putJson(route('admin.parties.update', $party->id), [
            'party_code' => $party->party_code,
            'name' => 'New Name',
            'mobile' => '9876543210',
            'is_active' => true,
        ])
        ->assertOk();

    Event::assertDispatched(PartyUpdated::class);
});

// ─── MODEL RELATIONSHIP TESTS ───────────────────────────────────────────────

test('party has active assignment relationship', function () {
    $party = Customer::factory()->create();
    $employee = User::factory()->fieldWorker()->create();

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee->id,
        'is_active' => true,
    ]);

    $party = $party->fresh(['activeAssignment.employee']);
    expect($party->activeAssignment)->not->toBeNull();
    expect($party->activeAssignment->employee->id)->toBe($employee->id);
});

test('party has assignments history relationship', function () {
    $party = Customer::factory()->create();
    $employee1 = User::factory()->fieldWorker()->create();
    $employee2 = User::factory()->fieldWorker()->create();

    PartyEmployeeMap::factory()->inactive()->create([
        'party_id' => $party->id,
        'employee_id' => $employee1->id,
    ]);

    PartyEmployeeMap::factory()->create([
        'party_id' => $party->id,
        'employee_id' => $employee2->id,
    ]);

    $party = $party->fresh(['assignments']);
    expect($party->assignments)->toHaveCount(2);
});
