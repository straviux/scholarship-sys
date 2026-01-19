<?php

namespace App\Console\Commands;

use App\Models\ScholarshipProfile;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CreateTestApplicants extends Command
{
    protected $signature = 'test:create-applicants {count=3}';
    protected $description = 'Create mock applicants with fake data for testing';

    public function handle()
    {
        $count = (int) $this->argument('count');

        // Get the first available scholarship program
        $program = ScholarshipProgram::orderBy('id')->first();

        if (!$program) {
            $this->error('❌ No scholarship program found in the database.');
            $this->info('Please create a scholarship program first.');
            return 1;
        }

        // Get the admin user or first user to use as created_by
        $adminUser = \App\Models\User::find(1);
        if (!$adminUser) {
            $adminUser = \App\Models\User::first();
        }

        if (!$adminUser) {
            $this->error('❌ No user found in the database.');
            $this->info('Please create a user first.');
            return 1;
        }

        // Get available courses and schools
        $courses = Course::take(20)->get();
        $schools = School::take(20)->get();

        // Authenticate the user for the command execution
        \Illuminate\Support\Facades\Auth::login($adminUser);

        $programId = $program->id;
        $created = [];
        $this->info("Using program: {$program->name} (ID: $programId)");
        $this->info("Creating records as user: {$adminUser->name}");
        if ($courses->count() > 0) {
            $this->info("Available courses: {$courses->count()}");
        }
        if ($schools->count() > 0) {
            $this->info("Available schools: {$schools->count()}\n");
        }

        try {
            for ($i = 0; $i < $count; $i++) {
                try {
                    $firstName = fake()->firstName();
                    $lastName = fake()->lastName();

                    // Create profile
                    $profile = ScholarshipProfile::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'middle_name' => fake()->firstName(),
                        'extension_name' => '',
                        'date_of_birth' => fake()->dateTimeThisDecade(),
                        'gender' => fake()->randomElement(['Male', 'Female']),
                        'place_of_birth' => fake()->city(),
                        'civil_status' => fake()->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
                        'religion' => fake()->randomElement(['Catholic', 'Muslim', 'Protestant', 'Buddhist', 'Other']),
                        'indigenous_group' => '',
                        'municipality' => fake()->city(),
                        'barangay' => fake()->word(),
                        'address' => fake()->streetAddress(),
                        'contact_no' => fake()->numerify('##########'),
                        'contact_no_2' => fake()->numerify('#########'),
                        'email' => fake()->email(),
                        'temporary_municipality' => fake()->city(),
                        'temporary_barangay' => fake()->word(),
                        'temporary_address' => fake()->streetAddress(),
                        'father_name' => fake()->firstName() . ' ' . $lastName,
                        'father_occupation' => fake()->jobTitle(),
                        'father_birthdate' => fake()->dateTimeThisDecade(),
                        'father_contact_no' => fake()->numerify('#########'),
                        'mother_name' => fake()->firstName() . ' ' . $lastName,
                        'mother_occupation' => fake()->jobTitle(),
                        'mother_birthdate' => fake()->dateTimeThisDecade(),
                        'mother_contact_no' => fake()->numerify('#########'),
                        'guardian_name' => fake()->firstName() . ' ' . $lastName,
                        'guardian_relationship' => fake()->randomElement(['Uncle', 'Aunt', 'Grandparent', 'Other']),
                        'guardian_occupation' => fake()->jobTitle(),
                        'guardian_contact_no' => fake()->numerify('#########'),
                        'parents_guardian_gross_monthly_income' => fake()->numberBetween(5000, 100000),
                        'is_jpm_member' => false,
                        'is_father_jpm' => false,
                        'is_mother_jpm' => false,
                        'is_guardian_jpm' => false,
                        'is_not_jpm' => false,
                        'jpm_remarks' => '',
                        'remarks' => 'Test record - ' . fake()->sentence(),
                        'date_filed' => now(),
                    ]);

                    // Refresh to ensure profile_id is populated
                    $profile = $profile->fresh();
                    $this->info("Created profile: $firstName $lastName (ID: {$profile->profile_id})");

                    // Create scholarship record with PENDING status
                    if ($profile && $profile->profile_id) {
                        $selectedCourse = $courses->count() > 0 ? $courses->random() : null;
                        $selectedSchool = $schools->count() > 0 ? $schools->random() : null;

                        $scholarship = $profile->scholarshipGrant()->create([
                            'program_id' => $programId,
                            'school_id' => $selectedSchool ? $selectedSchool->id : null,
                            'course_id' => $selectedCourse ? $selectedCourse->id : null,
                            'year_level' => fake()->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
                            'term' => fake()->randomElement(['1st Semester', '2nd Semester']),
                            'academic_year' => now()->year . '-' . (now()->year + 1),
                            'unified_status' => 'pending',
                            'date_filed' => now(),
                        ]);
                        $schoolName = $selectedSchool ? $selectedSchool->name : 'N/A';
                        $courseName = $selectedCourse ? $selectedCourse->name : 'N/A';
                        $this->info("  ✓ Created scholarship record: {$scholarship->id}");
                        $this->info("    • School: $schoolName");
                        $this->info("    • Course: $courseName");
                        $this->info("    • Year: {$scholarship->year_level}\n");

                        $created[] = $firstName . ' ' . $lastName . ' (ID: ' . $profile->profile_id . ')';
                    } else {
                        $this->warn("Failed to create record for $firstName $lastName: profile_id not set");
                    }
                } catch (\Exception $e) {
                    $this->error("Error on applicant $i: " . $e->getMessage());
                    throw $e;
                }
            }

            $this->info("\n✅ Created $count test applicants:");
            foreach ($created as $applicant) {
                $this->line("   • $applicant");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error creating test applicants: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
