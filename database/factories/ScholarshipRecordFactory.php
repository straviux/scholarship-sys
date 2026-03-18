<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScholarshipRecord>
 */
class ScholarshipRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $program = ScholarshipProgram::first() ?? ScholarshipProgram::create([
            'name' => 'Test Program ' . $this->faker->unique()->word(),
            'shortname' => strtoupper($this->faker->unique()->lexify('???')),
        ]);

        $course = Course::first() ?? Course::create([
            'name' => 'Test Course ' . $this->faker->unique()->word(),
            'shortname' => strtoupper($this->faker->unique()->lexify('???')),
            'scholarship_program_id' => $program->id,
        ]);

        $profile = ScholarshipProfile::first() ?? ScholarshipProfile::create([
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        $user = User::first() ?? User::factory()->create();

        return [
            'profile_id' => $profile->profile_id,
            'course_id' => $course->id,
            'program_id' => $program->id,
            'term' => $this->faker->randomElement(['1st', '2nd', 'Summer']),
            'academic_year' => '2024-2025',
            'year_level' => $this->faker->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }
}
