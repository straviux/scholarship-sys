<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScholarshipProfile;

class UpdateScholarshipProfileUniqueIds extends Command
{
    protected $signature = 'scholarship:update-unique-ids';
    protected $description = 'Update unique_id for all existing scholarship profiles';

    public function handle()
    {
        $profiles = ScholarshipProfile::all();
        $updated = 0;
        foreach ($profiles as $profile) {
            $year = date('y', strtotime($profile->created_at));
            $last = strtoupper(substr($profile->last_name, 0, 1));
            $first = strtoupper(substr($profile->first_name, 0, 1));
            if (!empty($profile->middle_name)) {
                $third = strtoupper(substr($profile->middle_name, 0, 1));
            } else {
                $third = strtoupper(substr($profile->first_name, 1, 1));
            }
            $initials = $last . $first . $third;
            $created = strtotime($profile->created_at);
            do {
                $timePart = substr((string)$created, -3);
                $uniqueId = $initials . $year . $timePart;
                $created++;
            } while (ScholarshipProfile::where('unique_id', $uniqueId)->where('profile_id', '!=', $profile->profile_id)->exists());
            $profile->unique_id = $uniqueId;
            $profile->save();
            $updated++;
        }
        $this->info("Updated $updated scholarship profiles with unique_id.");
    }
}
