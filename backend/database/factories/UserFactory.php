<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->optional()->phoneNumber(),
            'employee_id' => 'EMP' . fake()->unique()->numberBetween(1000, 9999),
            'address' => fake()->optional()->address(),
            'date_of_birth' => fake()->optional()->date('Y-m-d', '-25 years'),
            'hire_date' => fake()->optional()->date('Y-m-d', '-2 years'),
            'department' => fake()->optional()->randomElement(['Sales', 'Marketing', 'Operations', 'IT', 'HR']),
            'position' => fake()->optional()->randomElement(['Field Worker', 'Manager', 'Executive', 'Supervisor']),
            'salary' => fake()->optional()->numberBetween(25000, 100000),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the user is a field worker.
     */
    public function fieldWorker(): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => 'Sales',
            'position' => 'Field Worker',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
