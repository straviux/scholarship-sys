<?php

namespace App\Exports;

use App\Models\ScholarshipProfile;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WaitingListExport implements FromCollection, WithHeadings
{
    protected $profiles;

    public function __construct($profiles)
    {
        $this->profiles = $profiles;
    }

    public function collection()
    {
        // Sort profiles as in Blade template
        $sortedProfiles = collect($this->profiles)->sortBy(function ($profile) {
            $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
            return [$dateFiled, $profile->created_at];
        });

        $rows = [];
        $lastDate = null;
        $dateIndex = 1;
        $overallIndex = 1;
        foreach ($sortedProfiles as $profile) {
            $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
            $dateKey = $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('Y-m-d') : '';
            if ($dateKey !== $lastDate) {
                $dateIndex = 1;
                $lastDate = $dateKey;
            }
            $contacts = array_filter([
                $profile->contact_no ?? null,
                $profile->contact_no_2 ?? null
            ]);
            $row = [
                '#' => $overallIndex,
                'Seq' => $dateIndex,
                'Name' => $profile->last_name . ', ' . $profile->first_name,
                'Contact Nos.' => count($contacts) ? implode(' / ', $contacts) : '-',
                'Municipality' => $profile->municipality ?? '-',
                'Program' => optional(optional($profile->scholarshipGrant->first())->program)->shortname ?? '-',
                'School' => optional($profile->scholarshipGrant->first())->school->shortname ?? '-',
                'Course' => optional($profile->scholarshipGrant->first())->course->shortname ?? '-',
                'Year Level' => optional($profile->scholarshipGrant->first())->year_level ?? '-',
                'Date Filed' => $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('M d, Y') : '-',
            ];
            $rows[] = $row;
            $dateIndex++;
            $overallIndex++;
        }
        return collect($rows);
    }

    public function headings(): array
    {
        return [
            '#',
            'Seq',
            'Name',
            'Contact Nos.',
            'Municipality',
            'Program',
            'School',
            'Course',
            'Year Level',
            'Date Filed',
        ];
    }
}
