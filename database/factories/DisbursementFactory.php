<?php

namespace Database\Factories;

use App\Models\ScholarshipProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Disbursement>
 */
class DisbursementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profile = ScholarshipProfile::first() ?? ScholarshipProfile::create([
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        return [
            'profile_id' => $profile->profile_id,
            'disbursement_type' => $this->faker->randomElement(['allowance', 'stipend', 'tuition']),
            'payee' => $this->faker->name(),
        ];
    }
}
