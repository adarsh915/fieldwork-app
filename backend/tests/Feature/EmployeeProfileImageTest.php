<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeProfileImageTest extends TestCase
{
    protected function getAdminUser(): User
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $admin = User::firstOrCreate(
            ['email' => 'admin_test@ampl.com'],
            ['name' => 'Admin Test', 'password' => bcrypt('password'), 'is_active' => true]
        );
        if (!$admin->hasRole('Super Admin')) {
            $admin->assignRole($role);
        }
        return $admin;
    }

    public function test_employee_update_with_image_and_empty_password_succeeds(): void
    {
        Storage::fake('public');

        $admin = $this->getAdminUser();
        $this->actingAs($admin);

        $employee = User::where('id', '!=', $admin->id)->first() ?? User::factory()->create();

        $image = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->putJson(route('admin.employees.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => '',
            'salary' => '',
            'reporting_to' => '',
            'role' => $employee->roles->first()?->name ?? 'Employee',
            'profile_image' => $image,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertNotNull($employee->profile_image);
        Storage::disk('public')->assertExists($employee->profile_image);
    }

    public function test_employee_remove_profile_image(): void
    {
        Storage::fake('public');

        $admin = $this->getAdminUser();
        $this->actingAs($admin);

        $employee = User::where('id', '!=', $admin->id)->first() ?? User::factory()->create();

        // Set existing image
        $imagePath = 'employees/avatars/existing.jpg';
        Storage::disk('public')->put($imagePath, 'fake-content');
        $employee->update(['profile_image' => $imagePath]);

        $response = $this->putJson(route('admin.employees.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->roles->first()?->name ?? 'Employee',
            'remove_profile_image' => '1',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertNull($employee->profile_image);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_employee_update_with_string_is_active(): void
    {
        $admin = $this->getAdminUser();
        $this->actingAs($admin);

        $employee = User::where('id', '!=', $admin->id)->first() ?? User::factory()->create();

        // Test with string 'true' as FormData sends
        $response = $this->putJson(route('admin.employees.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => 'true',
            'role' => $employee->roles->first()?->name ?? 'Employee',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Test with string 'false' as FormData sends
        $response2 = $this->putJson(route('admin.employees.update', $employee->id), [
            'name' => $employee->name,
            'email' => $employee->email,
            'is_active' => 'false',
            'role' => $employee->roles->first()?->name ?? 'Employee',
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);
        $employee->refresh();
        $this->assertFalse((bool)$employee->is_active);
    }
}
