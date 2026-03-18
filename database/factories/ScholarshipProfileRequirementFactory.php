<?php

namespace Database\Factories;

use App\Models\Requirement;
use App\Models\ScholarshipProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipProfileRequirement>
 */
class ScholarshipProfileRequirementFactory extends Factory
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

        $requirement = Requirement::first() ?? Requirement::create([
            'name' => 'Test Requirement ' . $this->faker->unique()->word(),
        ]);

        return [
            'profile_id' => $profile->profile_id,
            'requirement_id' => $requirement->id,
        ];
    }
}
