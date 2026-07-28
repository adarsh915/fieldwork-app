<?php

namespace Database\Factories;

use App\Models\PartyEmployeeMap;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartyEmployeeMap>
 */
class PartyEmployeeMapFactory extends Factory
{
    protected $model = PartyEmployeeMap::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Customer::factory(),
            'employee_id' => User::factory()->fieldWorker(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'unassigned_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the assignment is inactive/historical.
     */
    public function inactive(): static
    {
        $assignedDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $unassignedDate = fake()->dateTimeBetween($assignedDate, 'now');
        
        return $this->state(fn (array $attributes) => [
            'assigned_at' => $assignedDate,
            'unassigned_at' => $unassignedDate,
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the assignment is active and recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'is_active' => true,
        ]);
    }
}
