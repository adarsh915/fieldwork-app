<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyTypes = ['Traders', 'Enterprises', 'Industries', 'Corp', 'Pvt Ltd', 'Solutions'];
        
        return [
            'party_code' => 'PTY' . fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->company() . ' ' . fake()->randomElement($companyTypes),
            'owner_name' => fake()->name(),
            'mobile' => fake()->numerify('##########'),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->numerify('######'),
            'latitude' => fake()->latitude(19.0, 20.0),
            'longitude' => fake()->longitude(72.0, 73.0),
            'land_area' => fake()->numberBetween(100, 5000) . ' sqft',
            'party_type' => fake()->randomElement(['Retailer', 'Distributor', 'Wholesaler', 'End Customer']),
            'category' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'credit_limit' => fake()->randomFloat(2, 10000, 500000),
            'credit_days' => fake()->numberBetween(15, 90),
            'opening_balance' => fake()->randomFloat(2, 0, 100000),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the party is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the party is a retailer.
     */
    public function retailer(): static
    {
        return $this->state(fn (array $attributes) => [
            'party_type' => 'Retailer',
            'category' => 'B',
            'credit_limit' => fake()->randomFloat(2, 10000, 50000),
            'credit_days' => 30,
        ]);
    }

    /**
     * Indicate that the party is a distributor.
     */
    public function distributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'party_type' => 'Distributor',
            'category' => 'A',
            'credit_limit' => fake()->randomFloat(2, 100000, 500000),
            'credit_days' => 60,
        ]);
    }
}
