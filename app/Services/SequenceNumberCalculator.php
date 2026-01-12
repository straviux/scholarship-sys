<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class SequenceNumberCalculator
{
    /**
     * Calculate sequence numbers for a collection of scholarship profiles
     * Prioritizes overall sequence_number (position in waiting list by date filed)
     *
     * @param Collection $profiles
     * @return Collection
     */
    public static function calculateSequenceNumbers(Collection $profiles): Collection
    {
        if ($profiles->isEmpty()) {
            return $profiles;
        }

        // Initialize all sequence numbers to null for all profiles
        $profiles->each(function ($profile) {
            $profile->sequence_number = null;
            $profile->sequence_number_by_course = null;
            $profile->sequence_number_by_school_course = null;
            $profile->daily_sequence_number = null;
        });

        // PRIMARY: Overall sequence number grouped by program
        // This is the queue position within each program in the waiting list
        // Requires program AND date_filed
        // Records without program or scholarship record will have sequence_number = null (displayed as "-")
        $withProgramAndDate = $profiles->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->date_filed && $grant->program;
        });

        // Group by program first, then sort by date_filed within each program
        $programGroups = $withProgramAndDate->groupBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant->program->id;
        });

        // Assign sequence numbers within each program group
        foreach ($programGroups as $programId => $programProfiles) {
            $sortedByDate = $programProfiles->sortBy(function ($profile) {
                $grant = optional($profile->scholarshipGrant)->first();
                return Carbon::parse($grant->date_filed)->format('Y-m-d H:i:s');
            })->values();

            $counter = 1;
            foreach ($sortedByDate as $profile) {
                $profile->sequence_number = $counter++;
            }
        }

        // SECONDARY: Group by course for sequence_number_by_course
        // Requires program AND date_filed AND course
        $withCourse = $withProgramAndDate->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->course;
        });

        $courseGroups = $withCourse->groupBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant->course->id;
        });

        foreach ($courseGroups as $courseId => $courseProfiles) {
            $counter = 1;
            foreach ($courseProfiles as $profile) {
                $profile->sequence_number_by_course = $counter++;
            }
        }

        // TERTIARY: Group by school + course for sequence_number_by_school_course
        // Requires program AND date_filed AND school AND course
        $withSchoolCourse = $withProgramAndDate->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->school && $grant->course;
        });

        $schoolCourseGroups = $withSchoolCourse->groupBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            $schoolId = $grant->school->id;
            $courseId = $grant->course->id;
            return "{$schoolId}_{$courseId}";
        });

        foreach ($schoolCourseGroups as $groupKey => $groupProfiles) {
            $counter = 1;
            foreach ($groupProfiles as $profile) {
                $profile->sequence_number_by_school_course = $counter++;
            }
        }

        // OPTIONAL: Daily sequence by date filed
        // Requires program AND date_filed AND course AND school
        $withDateAndSchoolCourse = $withProgramAndDate->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->course && $grant->school;
        });

        $dateGroups = $withDateAndSchoolCourse->groupBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return Carbon::parse($grant->date_filed)->format('Y-m-d');
        });

        foreach ($dateGroups as $date => $dateProfiles) {
            $counter = 1;
            foreach ($dateProfiles as $profile) {
                $profile->daily_sequence_number = $counter++;
            }
        }

        return $profiles;
    }
}
