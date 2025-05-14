<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomElement(['9b56a9ef-0374-48be-bd0f-4966361669f3', '9b56a9ef-5f47-4f12-92ea-506bbbb189fb']),
            'updated_by' => $this->faker->randomElement(['9b56a9ef-0374-48be-bd0f-4966361669f3', '9b56a9ef-5f47-4f12-92ea-506bbbb189fb']),
            'triggered_by' => $this->faker->randomElement(['9b56a9ef-0374-48be-bd0f-4966361669f3', '9b56a9ef-5f47-4f12-92ea-506bbbb189fb']),
            'reference_id' => $this->faker->randomNumber(6),
            'description' => $this->faker->sentence(),
            'service' => $this->faker->randomElement(['aeps', 'bbps', 'cms']),
            'credit_amount' => $this->faker->randomNumber(3),
            'debit_amount' => $this->faker->randomNumber(3),
            'opening_balance' => 0,
            'closing_balance' => 0,
            'metadata' => json_encode(['test']),
        ];
    }
}
