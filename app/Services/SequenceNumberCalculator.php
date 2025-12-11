<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class SequenceNumberCalculator
{
    /**
     * Calculate sequence numbers for a collection of scholarship profiles
     * These are relative to the visible set (current page), not the entire result set
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

        // Filter profiles with date_filed, course, school, and program for main sequence number
        $withDateFiled = $profiles->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->date_filed && $grant->course && $grant->school && $grant->program;
        });

        $sortedByDate = $withDateFiled->sortBy(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return Carbon::parse($grant->date_filed)->format('Y-m-d H:i:s');
        })->values(); // Reindex after sorting

        // Overall sequence number (1, 2, 3, ... based on date_filed)
        // Use manual counter to ensure strict sequencing of filtered records only
        $counter = 1;
        foreach ($sortedByDate as $profile) {
            $profile->sequence_number = $counter++;
        }

        // Group by course for sequence_number_by_course (exclude those without date_filed and without course)
        $withCourse = $withDateFiled->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->course;
        });

        // Track IDs of profiles that have courses to ensure strict filtering
        $withCourseIds = $withCourse->pluck('profile_id')->toArray();

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

        // Group by school + course combination for sequence_number_by_school_course (exclude those without date_filed and without both school and course)
        $withSchoolCourse = $withDateFiled->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->school && $grant->course;
        });

        // Track IDs of profiles that have both school and course to ensure strict filtering
        $withSchoolCourseIds = $withSchoolCourse->pluck('profile_id')->toArray();

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

        // Group by date filed for daily_sequence_number (exclude those without date_filed, course, or school)
        $withDate = $profiles->filter(function ($profile) {
            $grant = optional($profile->scholarshipGrant)->first();
            return $grant && $grant->date_filed && $grant->course && $grant->school;
        });

        // Track IDs of profiles eligible for daily sequence
        $dailySequenceIds = $withDate->pluck('profile_id')->toArray();

        $dateGroups = $withDate->groupBy(function ($profile) {
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
