<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement([Transaction::TYPE_COLLECTION, Transaction::TYPE_EXPENSE]),
            'amount' => fake()->randomFloat(2, 5, 500),
            'category' => fake()->randomElement(['Office Supplies', 'Transport', 'Food & Refreshments', 'Utilities', 'Maintenance', 'Other']),
            'description' => fake()->optional()->sentence(),
            'occurred_on' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
