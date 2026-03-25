<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FundTransaction>
 */
class FundTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disbursement_type' => $this->faker->randomElement(['disbursements', 'payroll']),
            'payee_type' => 'scholar',
            'payee_name' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
