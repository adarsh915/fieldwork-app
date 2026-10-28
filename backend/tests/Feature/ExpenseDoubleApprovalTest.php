<?php

namespace Tests\Feature;

use App\Models\ExpenseBill;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseDoubleApprovalTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions exist
        Permission::findOrCreate('expenses.view', 'web');
        Permission::findOrCreate('expenses.view-own', 'web');
        Permission::findOrCreate('expenses.view-all', 'web');
        Permission::findOrCreate('expenses.view-team', 'web');
        Permission::findOrCreate('expenses.approve', 'web');
        Permission::findOrCreate('expenses.final_approve', 'web');
        Permission::findOrCreate('expenses.edit', 'web');
        Permission::findOrCreate('day-start.view-all', 'web');
        Role::findOrCreate('Admin', 'web');
        Role::findOrCreate('Super Admin', 'web');
        Role::findOrCreate('Manager', 'web');
        Role::findOrCreate('VP', 'web');
        Role::findOrCreate('HR', 'web');
    }

    public function test_manager_can_temp_approve_expense(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve']);

        $worker = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 500,
            'date'    => now()->toDateString(),
            'status'  => 'pending',
        ]);

        $this->actingAs($manager);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'temp_approve',
            'note'   => 'Verified bills against route travel',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'temp_approved']);

        $expense->refresh();
        $this->assertEquals('temp_approved', $expense->status);
        $this->assertEquals($manager->id, $expense->temp_approved_by);
        $this->assertNotNull($expense->temp_approved_at);
        $this->assertEquals('Verified bills against route travel', $expense->temp_approved_note);
    }

    public function test_manager_can_reject_expense_to_manager_rejected_status(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve']);

        $worker = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 350,
            'date'    => now()->toDateString(),
            'status'  => 'pending',
        ]);

        $this->actingAs($manager);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'manager_reject',
            'note'   => 'Missing toll receipt proof',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'manager_rejected']);

        $expense->refresh();
        $this->assertEquals('manager_rejected', $expense->status);
        $this->assertEquals($manager->id, $expense->rejected_by);
        $this->assertNotNull($expense->rejected_at);
        $this->assertEquals('Missing toll receipt proof', $expense->rejection_reason);
    }

    public function test_manager_without_final_permission_cannot_final_approve_verified_expense(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve']);

        $worker = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 1200,
            'date'    => now()->toDateString(),
            'status'  => 'temp_approved',
            'temp_approved_by' => $manager->id,
        ]);

        $this->actingAs($manager);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'Trying to bypass HR',
        ]);

        $response->assertStatus(403);
        $expense->refresh();
        $this->assertEquals('temp_approved', $expense->status);
    }

    public function test_hr_or_admin_can_final_approve_verified_expense(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo(['expenses.view', 'expenses.final_approve']);

        $worker = User::factory()->create();
        $manager = User::factory()->create();

        $expense = ExpenseBill::create([
            'user_id'            => $worker->id,
            'amount'             => 750,
            'date'               => now()->toDateString(),
            'status'             => 'temp_approved',
            'temp_approved_by'   => $manager->id,
            'temp_approved_at'   => now()->subHour(),
            'temp_approved_note' => 'Manager verified',
        ]);

        $this->actingAs($admin);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'Cleared for payroll disbursement',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'approved']);

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($admin->id, $expense->approved_by);
        $this->assertNotNull($expense->approved_at);
        $this->assertEquals('Cleared for payroll disbursement', $expense->final_note);
        $this->assertEquals($manager->id, $expense->temp_approved_by);
    }

    public function test_hr_can_override_manager_rejected_expense(): void
    {
        $hr = User::factory()->create();
        $hr->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.final_approve']);

        $worker = User::factory()->create();
        $manager = User::factory()->create();

        $expense = ExpenseBill::create([
            'user_id'          => $worker->id,
            'amount'           => 1500,
            'date'             => now()->toDateString(),
            'status'           => 'manager_rejected',
            'rejected_by'      => $manager->id,
            'rejection_reason' => 'Manager said no bill',
        ]);

        $this->actingAs($hr);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'HR approved after employee provided hardcopy original',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'approved']);

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($hr->id, $expense->approved_by);
    }

    public function test_hr_can_final_reject_expense(): void
    {
        $hr = User::factory()->create();
        $hr->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.final_approve']);

        $worker = User::factory()->create();

        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 800,
            'date'    => now()->toDateString(),
            'status'  => 'temp_approved',
        ]);

        $this->actingAs($hr);

        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_reject',
            'note'   => 'Duplicate claim detected by finance audit',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'rejected']);

        $expense->refresh();
        $this->assertEquals('rejected', $expense->status);
        $this->assertEquals($hr->id, $expense->rejected_by);
        $this->assertEquals('Duplicate claim detected by finance audit', $expense->rejection_reason);
    }

    public function test_approval_and_rejection_buttons_render_with_solid_background_styles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $admin->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve', 'expenses.final_approve']);

        $worker = User::factory()->create();
        $tourPurpose = \App\Models\TourPurpose::firstOrCreate(['name' => 'Client Visit']);
        $tourType = \App\Models\TourType::firstOrCreate(['name' => 'HQ']);
        $vehicleType = \App\Models\VehicleType::firstOrCreate(['name' => 'Bike']);
        $dayStart = \App\Models\DayStart::create([
            'user_id' => $worker->id,
            'tour_purpose_id' => $tourPurpose->id,
            'tour_type_id' => $tourType->id,
            'vehicle_type_id' => $vehicleType->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'opening_km' => 100,
            'status' => 'pending',
        ]);

        ExpenseBill::create([
            'user_id' => $worker->id,
            'day_start_id' => $dayStart->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        // Day starts show page
        Permission::findOrCreate('day-start.view-all', 'web');
        $admin->givePermissionTo('day-start.view-all');

        $response = $this->get(route('admin.day-starts.show', $dayStart->id));
        $response->assertStatus(200);
        $content = $response->getContent();

        // Must have solid green for Final Approve and solid red for Reject
        $this->assertStringContainsString('background-color: #16a34a', $content, 'Final approve button must have green background style');
        $this->assertStringContainsString('background-color: #dc2626', $content, 'Reject button must have red background style');
    }

    public function test_expense_audit_names_and_history_rendered_on_day_starts_show(): void
    {
        $manager = User::factory()->create(['name' => 'Manager Suresh']);
        $admin = User::factory()->create(['name' => 'Admin Priya']);
        $admin->assignRole('Admin');
        $admin->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve', 'expenses.final_approve', 'day-start.view-all']);

        $worker = User::factory()->create(['name' => 'Worker Ramesh']);
        $tourPurpose = \App\Models\TourPurpose::firstOrCreate(['name' => 'Field Audit']);
        $tourType = \App\Models\TourType::firstOrCreate(['name' => 'HQ Travel']);
        $vehicleType = \App\Models\VehicleType::firstOrCreate(['name' => 'Scooter']);
        $dayStart = \App\Models\DayStart::create([
            'user_id' => $worker->id,
            'tour_purpose_id' => $tourPurpose->id,
            'tour_type_id' => $tourType->id,
            'vehicle_type_id' => $vehicleType->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'opening_km' => 100,
            'status' => 'approved',
            'approved_by' => $manager->id,
        ]);

        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'day_start_id' => $dayStart->id,
            'amount' => 750,
            'date' => now()->toDateString(),
            'status' => 'temp_approved',
            'temp_approved_by' => $manager->id,
            'temp_approved_at' => now(),
            'temp_approved_note' => 'Route meter reading verified by Suresh',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.day-starts.show', $dayStart->id));
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. DayStart header shows who approved the tour
        $this->assertStringContainsString('Manager Suresh', $content);

        // 2. Expense row shows verifier Suresh and note
        $this->assertStringContainsString('Route meter reading verified by Suresh', $content);

        // 3. Log history button is present
        $this->assertStringContainsString('Log', $content);
        $this->assertStringContainsString('Verification & Approval Log', $content);
    }

    public function test_rejecting_expense_requires_rejection_reason_message(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.approve']);

        $worker = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 400,
            'date'    => now()->toDateString(),
            'status'  => 'pending',
        ]);

        $this->actingAs($manager);

        // Rejection without note should fail validation
        $response = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'manager_reject',
            'note'   => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['note']);

        // Rejection with note should succeed and notify user
        $responseSuccess = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'manager_reject',
            'note'   => 'Missing valid receipt for fuel pump',
        ]);
        $responseSuccess->assertStatus(200);

        $expense->refresh();
        $this->assertEquals('manager_rejected', $expense->status);
        $this->assertEquals('Missing valid receipt for fuel pump', $expense->rejection_reason);

        // UserNotification created
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $worker->id,
            'screen'  => 'ExpenseList',
        ]);
    }

    public function test_user_with_edit_permission_can_edit_all_fields_and_replace_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = User::factory()->create();
        $admin->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.edit']);

        $categoryOld = \App\Models\ExpenseCategory::firstOrCreate(['name' => 'Food'], ['is_active' => true]);
        $categoryNew = \App\Models\ExpenseCategory::firstOrCreate(['name' => 'Hotel'], ['is_active' => true]);

        $worker = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id'     => $worker->id,
            'category_id' => $categoryOld->id,
            'amount'      => 500,
            'date'        => now()->toDateString(),
            'bill_photo'  => 'expenses/old.jpg',
            'status'      => 'pending',
        ]);

        $this->actingAs($admin);

        $fakePhoto = \Illuminate\Http\UploadedFile::fake()->image('new_receipt.jpg');

        $response = $this->post(route('admin.expenses.update', $expense->id), [
            '_method'     => 'PATCH',
            'category_id' => $categoryNew->id,
            'amount'      => 750,
            'date'        => now()->toDateString(),
            'status'      => 'pending',
            'bill_photo'  => $fakePhoto,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $expense->refresh();
        $this->assertEquals(750, $expense->amount);
        $this->assertEquals($categoryNew->id, $expense->category_id);
        $this->assertNotEquals('expenses/old.jpg', $expense->bill_photo);
        $this->assertNotNull($expense->bill_photo_url);
    }

    public function test_mobile_api_field_worker_can_edit_and_resubmit_rejected_expense(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $worker = User::factory()->create();
        $category = \App\Models\ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['is_active' => true]);

        $rejectedByManager = User::factory()->create();
        $expense = ExpenseBill::create([
            'user_id'          => $worker->id,
            'category_id'      => $category->id,
            'amount'           => 300,
            'date'             => now()->toDateString(),
            'bill_photo'       => 'expenses/bad_photo.jpg',
            'status'           => 'rejected',
            'rejection_reason' => 'Image blurry and unreadable',
            'rejected_by'      => $rejectedByManager->id,
            'rejected_at'      => now(),
        ]);

        $fakePhoto = \Illuminate\Http\UploadedFile::fake()->image('clear_receipt.jpg');

        $response = $this->actingAs($worker, 'sanctum')->postJson("/api/expenses/{$expense->id}", [
            'category_id' => $category->id,
            'amount'      => 320,
            'date'        => now()->toDateString(),
            'bill_photo'  => $fakePhoto,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Expense updated and resubmitted successfully.',
        ]);

        $expense->refresh();
        $this->assertEquals('pending', $expense->status);
        $this->assertNull($expense->rejection_reason);
        $this->assertNull($expense->rejected_by);
        $this->assertNull($expense->rejected_at);
        $this->assertEquals(320, $expense->amount);
        $this->assertNotEquals('expenses/bad_photo.jpg', $expense->bill_photo);
    }

    public function test_admin_can_edit_system_generated_auto_travel_expense(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.edit']);

        $worker = User::factory()->create();
        $travelCategory = \App\Models\ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['is_active' => true]);

        $tourPurpose = \App\Models\TourPurpose::first() ?? \App\Models\TourPurpose::create(['name' => 'Client Visit', 'is_active' => true]);
        $tourType = \App\Models\TourType::first() ?? \App\Models\TourType::create(['name' => 'Local', 'is_active' => true]);
        $vehicleType = \App\Models\VehicleType::first() ?? \App\Models\VehicleType::create(['name' => 'Bike', 'is_active' => true]);

        $dayStart = \App\Models\DayStart::create([
            'user_id' => $worker->id,
            'tour_purpose_id' => $tourPurpose->id,
            'tour_type_id' => $tourType->id,
            'vehicle_type_id' => $vehicleType->id,
            'opening_km' => 100,
            'closing_km' => 150,
            'total_amount' => 250,
            'date' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $trip = \App\Models\Trip::create([
            'user_id' => $worker->id,
            'day_start_id' => $dayStart->id,
            'vehicle_type_id' => $vehicleType->id,
            'opening_km' => 100,
            'closing_km' => 150,
            'distance_km' => 50,
            'total_amount' => 250,
            'date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'category_id' => $travelCategory->id,
            'day_start_id' => $dayStart->id,
            'is_system_generated' => true,
            'amount' => 250,
            'date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        // Verify index JSON shows can_edit = true for this auto travel expense
        $indexResponse = $this->getJson(route('admin.expenses.index'));
        $indexResponse->assertStatus(200);
        $records = collect($indexResponse->json('records'));
        $found = $records->firstWhere('id', $expense->id);
        $this->assertNotNull($found);
        $this->assertTrue($found['is_system_generated']);
        $this->assertTrue($found['can_edit'], 'Auto travel expense must have can_edit = true for authorized admin');

        // Update the auto travel expense
        $response = $this->post(route('admin.expenses.update', $expense->id), [
            '_method' => 'PATCH',
            'amount' => 300,
            'final_note' => 'Corrected route travel allowance',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $expense->refresh();
        $this->assertEquals(300, (float) $expense->amount);
        $this->assertEquals('Corrected route travel allowance', $expense->final_note);

        // Verify synchronization with DayStart and Trip
        $dayStart->refresh();
        $trip->refresh();
        $this->assertEquals(300, (float) $dayStart->total_amount);
        $this->assertEquals(300, (float) $trip->total_amount);
    }

    public function test_hierarchical_reporting_manager_and_hr_approval_gate(): void
    {
        // Manager A (Nikum Sir)
        $managerA = User::factory()->create(['name' => 'Nikum Sir']);
        $managerA->givePermissionTo(['expenses.view', 'expenses.view-team', 'expenses.approve']);

        // Manager B (Other Manager)
        $managerB = User::factory()->create(['name' => 'Other Manager']);
        $managerB->givePermissionTo(['expenses.view', 'expenses.view-team', 'expenses.approve']);

        // Worker reports strictly to Manager A
        $worker = User::factory()->create(['reporting_to' => $managerA->id]);

        // HR user
        $hr = User::factory()->create(['name' => 'HR User']);
        $hr->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.final_approve']);

        // Expense created by worker (status: pending)
        $expense = ExpenseBill::create([
            'user_id' => $worker->id,
            'amount'  => 850,
            'date'    => now()->toDateString(),
            'status'  => 'pending',
        ]);

        // 1. HR tries to final approve while pending -> MUST BE BLOCKED (422)
        $this->actingAs($hr);
        $hrBlockedResponse = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'Attempting direct HR bypass',
        ]);
        $hrBlockedResponse->assertStatus(422);
        $hrBlockedResponse->assertJson([
            'success' => false,
            'message' => 'Cannot Final Approve: This claim requires Level 1 verification from the direct reporting manager first.',
        ]);

        // 2. Manager B (unrelated manager) tries to Level 1 verify -> MUST BE BLOCKED (403)
        $this->actingAs($managerB);
        $managerBBlockedResponse = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'temp_approve',
            'note'   => 'Wrong manager approval',
        ]);
        $managerBBlockedResponse->assertStatus(403);

        // 3. Manager A (Nikum Sir, direct reporting manager) verifies -> MUST SUCCEED (200)
        $this->actingAs($managerA);
        $managerAApprovedResponse = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'temp_approve',
            'note'   => 'Verified daily travel by Nikum Sir',
        ]);
        $managerAApprovedResponse->assertStatus(200);
        $expense->refresh();
        $this->assertEquals('temp_approved', $expense->status);
        $this->assertEquals($managerA->id, $expense->temp_approved_by);

        // 4. Now HR can perform Final Approval -> MUST SUCCEED (200)
        $this->actingAs($hr);
        $hrSuccessResponse = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'Cleared for payment payout by HR',
        ]);
        $hrSuccessResponse->assertStatus(200);
        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($hr->id, $expense->approved_by);
        $this->assertEquals('Cleared for payment payout by HR', $expense->final_note);

        // 5. Manager A receives notification of HR final approval
        $this->assertTrue(
            \App\Models\UserNotification::where('user_id', $managerA->id)
                ->where('title', 'Team Expense Approved by HR')
                ->exists(),
            'Manager A must receive in-app notification when HR final approves their subordinate expense'
        );
    }

    public function test_vp_approval_notifies_hr_and_manager_without_hardcoded_roles(): void
    {
        Role::findOrCreate('VP', 'web');
        Role::findOrCreate('Manager', 'web');
        Role::findOrCreate('HR', 'web');

        // Hierarchy: Manager -> VP
        $vp = User::factory()->create(['name' => 'Abhinav VP']);
        $vp->assignRole('VP');
        $vp->givePermissionTo(['expenses.view', 'expenses.view-team', 'expenses.final_approve']);

        $manager = User::factory()->create(['name' => 'Ajay Manager', 'reporting_to' => $vp->id]);
        $manager->assignRole('Manager');
        $manager->givePermissionTo(['expenses.view', 'expenses.view-own', 'expenses.approve']);

        // HR user not in the reporting chain
        $hr = User::factory()->create(['name' => 'Pooja HR']);
        $hr->assignRole('HR');
        $hr->givePermissionTo(['expenses.view', 'expenses.view-all', 'expenses.final_approve']);

        // Manager submits an expense, verified by VP (since VP is direct manager), now VP final approves
        $expense = ExpenseBill::create([
            'user_id' => $manager->id,
            'amount'  => 1200,
            'date'    => now()->toDateString(),
            'status'  => 'temp_approved',
            'temp_approved_by' => $vp->id,
            'temp_approved_at' => now(),
        ]);

        $this->actingAs($vp);
        $res = $this->patchJson(route('admin.expenses.updateStatus', $expense->id), [
            'action' => 'final_approve',
            'note'   => 'Approved by VP',
        ]);

        $res->assertStatus(200);

        // 1. Manager (Submitter) must receive both mobile and web notifications
        $this->assertTrue(
            \App\Models\UserNotification::where('user_id', $manager->id)
                ->where('title', 'Expense Final Approved')
                ->exists(),
            'Manager must receive mobile notification for their approved expense'
        );
        $this->assertTrue(
            $manager->unreadNotifications()->where('data->expense_id', $expense->id)->exists(),
            'Manager must receive web admin notification for their approved expense'
        );

        // 2. HR (Cross-functional approver) must receive notification even though not in direct reporting chain
        $this->assertTrue(
            $hr->unreadNotifications()->where('data->expense_id', $expense->id)->exists(),
            'HR must receive notification of final approval to process payout'
        );

        // 3. VP (The Approver) must NOT receive a self-notification
        $this->assertFalse(
            $vp->unreadNotifications()->where('data->expense_id', $expense->id)->exists(),
            'VP must not receive self-notification'
        );

        // 4. Notification title must dynamically reflect VP role
        $hrNotif = $hr->unreadNotifications()->where('data->expense_id', $expense->id)->first();
        $this->assertNotNull($hrNotif);
        $this->assertEquals('Team Expense Approved by VP', $hrNotif->data['title']);
        $this->assertStringContainsString('Abhinav VP', $hrNotif->data['message']);
    }
}


