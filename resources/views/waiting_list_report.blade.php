<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Report</title>
    <style>
        /* Minimalist Material Design */
        body {
            font-family: 'Roboto', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 12px;
            color: #111827;
            padding: 0.05rem 0.3rem;
            margin: 0;
            background: #ffffff;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 0.2rem;
            color: #111827;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.2rem;
            color: #111827;
        }

        h3 {
            font-size: 1rem;
            font-weight: 400;
            margin-bottom: 0.15rem;
            color: #374151;
        }

        /* Minimalist Tables */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 0.15em;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        th,
        td {
            border: none;
            border-bottom: 1px solid #f3f4f6;
            padding: 0.1rem 0.12rem;
            text-align: left;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 180px;
            vertical-align: top;
        }

        /* Minimalist Table Header */
        th {
            background: #f9fafb;
            font-weight: 500;
            color: #374151;
            font-size: 0.88em;
            padding: 0.12rem;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Subtle hover effect */
        tbody tr:hover {
            background-color: #f9fafb;
        }

        .summary-table {
            margin-bottom: 0.4em;
        }

        /* Minimalist Filter Badge */
        .filters {
            margin-bottom: 0.2rem;
            padding: 0.2rem 0.3rem;
            background: #eff6ff;
            border-radius: 6px;
            border: 1px solid #dbeafe;
            color: #1e40af;
            font-size: 0.85rem;
        }

        .badge {
            display: inline-block;
            background: #ffffff;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 0.06rem 0.25rem;
            margin-right: 0.25rem;
            font-size: 0.82rem;
            font-weight: 400;
        }

        /* Minimalist School Header */
        .school-header {
            border-bottom: 2px solid #d1d5db;
            padding: 8px 0;
            margin-top: 24px;
            margin-bottom: 8px;
        }

        /* Minimalist Report Header */
        .report-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        /* Minimalist JPM Highlight */
        .jpm-row {
            background-color: #ecfdf5 !important;
        }

        @media print {
            body {
                padding-bottom: 0.2cm;
            }

            .report-footer {
                position: fixed;
                bottom: 0.1cm;
                left: 0;
                right: 0;
                padding-right: 0.1cm;
            }

            table {
                border: 1px solid #e5e7eb;
            }
        }

        @media print and (orientation: portrait) {
            .report-footer {
                font-size: 0.85em;
            }
        }

        @media print and (orientation: landscape) {
            .report-footer {
                font-size: 1.2em;
            }
        }
    </style>
</head>

<body>
    <?php
    $pgpLogoSvg = file_get_contents(public_path('images/pgp-logo.svg'));
    $yakapLogoSvg = file_get_contents(public_path('images/yakap-logo.svg'));
    $pgpLogoBase64 = base64_encode($pgpLogoSvg);
    $yakapLogoBase64 = base64_encode($yakapLogoSvg);
    ?>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
        <img src="data:image/svg+xml;base64,<?php echo $pgpLogoBase64; ?>" alt="PGP Logo" style="height: 72px; width: auto; margin-left: 0.5rem;">
        <div style="flex: 1; text-align: center;">
            <h1 style="margin:0; font-size:14px; font-weight:500; color:#333;">Republic of the Philippines</h1>
            <h1 style="margin:0; font-size:14px; font-weight:500; color:#333;">Provincial Government of Palawan</h1>
            <h1 style="margin:0; font-size:14px; font-weight:600; color:#333;">Akbay sa Mag-Aaral Yaman ng Kinabukasan</h1>
            <h1 style="margin:0; font-size:14px; font-weight:600; color:#333;">(Programang Pang-Edukasyon para sa Palaweño)</h1>
        </div>
        <img src="data:image/svg+xml;base64,<?php echo $yakapLogoBase64; ?>" alt="Yakap Logo" style="height: 72px; width: auto; margin-right: 0.5rem;">
    </div>
    <div class="filters" style="display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; margin-bottom: 0.5rem; font-size: 11px; color: #6b7280;">
        <span>{{ ucfirst($reportType) }}</span>
        @foreach($filters as $key => $value)
        @if($value && !in_array($key, ['paper_size', 'orientation', 'date_filed']))
        <span style="color:#d1d5db;">•</span>
        @if($key === 'approval_status')
        <!-- Display unified status from request parameter -->
        <span>{{ is_array($value) ? implode(', ', array_map('ucwords', str_replace('_', ' ', $value))) : ucwords(str_replace('_', ' ', $value)) }}</span>
        @if($key === 'grant_provision')
        <span>{{ is_array($value) ? implode(', ', array_map('ucwords', str_replace('_', ' ', $value))) : ucwords(str_replace('_', ' ', $value)) }}</span>
        @elseif($key === 'program' && isset($profiles) && count($profiles) && optional($profiles->first()->scholarshipGrant->first())->program)
        <span>{{ optional($profiles->first()->scholarshipGrant->first())->program->name }}</span>
        @elseif(in_array($key, ['course', 'courses']) && isset($profiles) && count($profiles) && optional($profiles->first()->scholarshipGrant->first())->course)
        <span>{{ optional($profiles->first()->scholarshipGrant->first())->course->name }}</span>
        @elseif($key === 'show_jpm_only' && $value)
        <span>JPM Members</span>
        @elseif($key === 'hide_jpm' && $value)
        <span></span>
        @else
        <span>{{ $value }}</span>
        @endif
        @endif
        @endif
        @endforeach
        @php
        // Calculate date range from the profiles
        $dates = $profiles->map(function($profile) {
        $grant = optional($profile->scholarshipGrant->first());
        return $grant->date_filed ?? $grant->date_approved ?? $grant->created_at;
        })->filter()->map(function($date) {
        return \Carbon\Carbon::parse($date);
        })->sortBy(function($date) {
        return $date->timestamp;
        });

        if ($dates->isNotEmpty()) {
        $oldestDate = $dates->first()->format('M d, Y');
        $latestDate = $dates->last()->format('M d, Y');
        }
        @endphp
        @if(isset($oldestDate) && isset($latestDate))
        <span style="color:#d1d5db;">•</span>
        <span>{{ $oldestDate }} to {{ $latestDate }}</span>
        @endif
    </div>
    <div style="font-size: 10px; color: #9ca3af; margin-bottom: 1rem;">
        Generated: {{ now()->timezone('Asia/Manila')->format('F d, Y h:i A') }}
    </div>

    <!-- Minimalist Report Header -->
    <div class="report-header">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <div>
                <h1 style="margin: 0; font-size: 14px; font-weight: 300; color: #111827;">
                    Pending Applications
                </h1>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">
                    {{ $profiles->count() }} records
                </p>
            </div>
        </div>
    </div>

    @if($reportType === 'summary')
    <table class="summary-table">
        @php
        // Determine which summary sections to show based on group_by
        // Allow school and course to show even when filtered (to show breakdown of selected items)
        $showProgram = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'program');
        $showSchool = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'school');
        $showCourse = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'course');
        $showYearLevel = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'year_level');

        $total = $summary['total'] ?? 1; // Avoid division by zero
        @endphp
        @if(isset($summary['by_program']) && $showProgram)
        <tr>
            <th colspan="3">By Program</th>
        </tr>
        @php
        $programData = collect($summary['by_program'])->filter(function($count, $name) {
        return $name !== 'no_program';
        })->sortDesc();
        $programIndex = 1;
        @endphp
        @foreach($programData as $name => $count)
        <tr>
            <td>{{ $programIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_program']['no_program']))
        <tr>
            <td><em>{{ $programIndex }}. No Program</em></td>
            <td>{{ $summary['by_program']['no_program'] }}</td>
            <td>{{ number_format(($summary['by_program']['no_program'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_school']) && $showSchool)
        <tr>
            <th colspan="3">By School</th>
        </tr>
        @php
        $schoolData = collect($summary['by_school'])->filter(function($count, $name) {
        return $name !== 'no_school';
        })->sortDesc();
        $schoolIndex = 1;
        @endphp
        @foreach($schoolData as $name => $count)
        <tr>
            <td>{{ $schoolIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_school']['no_school']))
        <tr>
            <td><em>{{ $schoolIndex }}. No School</em></td>
            <td>{{ $summary['by_school']['no_school'] }}</td>
            <td>{{ number_format(($summary['by_school']['no_school'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_course']) && $showCourse)
        <tr>
            <th colspan="3">By Course</th>
        </tr>
        @php
        $courseData = collect($summary['by_course'])->filter(function($count, $name) {
        return $name !== 'no_course';
        })->sortDesc();
        $courseIndex = 1;
        @endphp
        @foreach($courseData as $name => $count)
        <tr>
            <td>{{ $courseIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_course']['no_course']))
        <tr>
            <td><em>{{ $courseIndex }}. No Course</em></td>
            <td>{{ $summary['by_course']['no_course'] }}</td>
            <td>{{ number_format(($summary['by_course']['no_course'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_year_level']) && $showYearLevel)
        <tr>
            <th colspan="3">By Year Level</th>
        </tr>
        @php
        $yearLevelData = collect($summary['by_year_level'])->filter(function($count, $name) {
        return $name !== 'no_year_level';
        })->sortDesc();
        $yearLevelIndex = 1;
        @endphp
        @foreach($yearLevelData as $name => $count)
        <tr>
            <td>{{ $yearLevelIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_year_level']['no_year_level']))
        <tr>
            <td><em>{{ $yearLevelIndex }}. No Year Level</em></td>
            <td>{{ $summary['by_year_level']['no_year_level'] }}</td>
            <td>{{ number_format(($summary['by_year_level']['no_year_level'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif
    </table>
    @else
    @php
    // Use grouped profiles if available, otherwise use original sorting
    $profilesToIterate = $groupedProfiles ?? collect(['all' => $profiles]);

    // Initialize sequence counters for different groupings
    $programSequences = [];
    $schoolSequences = [];
    $courseSequences = [];

    // Initialize queue number counters (Q#) for program, school, and course
    // These track position within each group based on date filed order
    $programQueueNumbers = [];
    $schoolQueueNumbers = [];
    $courseQueueNumbers = [];

    $overallIndex = 1;
    @endphp

    @foreach($profilesToIterate as $groupName => $groupProfiles)
    @php
    // Sort profiles within each group by date filed (oldest to newest)
    $sortedProfiles = $groupProfiles->sortBy(function($profile) {
    $grant = optional($profile->scholarshipGrant->first());
    $dateFiled = $grant->date_filed ? \Carbon\Carbon::parse($grant->date_filed)->format('Y-m-d') : '9999-12-31';
    $createdAt = $profile->created_at ? \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';
    return [$dateFiled, $createdAt];
    });
    $groupCount = $sortedProfiles->count();
    @endphp

    @if($groupedProfiles && $groupBy !== 'none')
    <!-- Minimalist Group Header -->
    <div class="school-header">
        <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <h2 style="margin: 0; font-size: 15px; font-weight: 500; color: #111827;">
                {{ strtoupper($groupName) }}
            </h2>
            <span style="font-size: 12px; color: #6b7280;">
                {{ $groupCount }} records
            </span>
        </div>
    </div>
    @endif

    <table style="@if($groupedProfiles && $groupBy !== 'none') margin-top: 0; @endif @if(!$loop->last) margin-bottom: 20px; @endif">
        <thead>
            <tr>
                <th style="min-width:30px;color:#555;padding-left:0.1cm;padding-right:0.1cm;">#</th>
                <th>Name</th>
                <th>Contact No(s).</th>
                @if(empty($filters['municipality']) && (!isset($groupBy) || $groupBy !== 'municipality'))
                <th>Municipality</th>
                @endif
                @if(empty($filters['program']) && (!isset($groupBy) || $groupBy !== 'program'))
                <th>Program</th>
                @endif
                @if(empty($filters['school']) && (!isset($groupBy) || $groupBy !== 'school'))
                <th>School</th>
                @endif
                @if(empty($filters['course']) && empty($filters['courses']) && (!isset($groupBy) || $groupBy !== 'course'))
                <th>Course</th>
                @endif
                @if(empty($filters['year_level']) && (!isset($groupBy) || $groupBy !== 'year_level'))
                <th style="width:35px">Level</th>
                @endif
                <th>Remarks</th>
                <th style="width:68px">Date Filed (mm/dd/Y)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sortedProfiles as $profile)
            @php
            $grant = optional($profile->scholarshipGrant->first());
            $programName = optional($grant->program)->shortname ?? optional($grant->program)->name ?? 'no_program';
            $schoolName = optional($grant->school)->shortname ?? optional($grant->school)->name ?? 'no_school';
            $courseName = optional($grant->course)->shortname ?? optional($grant->course)->name ?? 'no_course';

            // Calculate sequence for Program grouping
            if (!isset($programSequences[$programName])) {
            $programSequences[$programName] = 0;
            }
            $programSequences[$programName]++;
            $programSeqNum = $programSequences[$programName];

            // Calculate sequence for School grouping
            if (!isset($schoolSequences[$schoolName])) {
            $schoolSequences[$schoolName] = 0;
            }
            $schoolSequences[$schoolName]++;
            $schoolSeqNum = $schoolSequences[$schoolName];

            // Calculate sequence for Course grouping
            if (!isset($courseSequences[$courseName])) {
            $courseSequences[$courseName] = 0;
            }
            $courseSequences[$courseName]++;
            $courseSeqNum = $courseSequences[$courseName];

            // Calculate Queue Numbers (Q#) per Program, School, and Course
            // These represent the position in the waiting list for each grouping
            // Exclude records without valid program/school/course from queue numbers

            // Q# for Program (per program by date filed) - only if program exists
            $programQNum = '-';
            if ($programName !== 'no_program' && $grant && $grant->program) {
            if (!isset($programQueueNumbers[$programName])) {
            $programQueueNumbers[$programName] = 0;
            }
            $programQueueNumbers[$programName]++;
            $programQNum = $programQueueNumbers[$programName];
            }

            // Q# for School (per school by date filed) - only if school exists
            $schoolQNum = '-';
            if ($schoolName !== 'no_school' && $grant && $grant->school) {
            if (!isset($schoolQueueNumbers[$schoolName])) {
            $schoolQueueNumbers[$schoolName] = 0;
            }
            $schoolQueueNumbers[$schoolName]++;
            $schoolQNum = $schoolQueueNumbers[$schoolName];
            }

            // Q# for Course (per course by date filed) - only if course exists
            $courseQNum = '-';
            if ($courseName !== 'no_course' && $grant && $grant->course) {
            if (!isset($courseQueueNumbers[$courseName])) {
            $courseQueueNumbers[$courseName] = 0;
            }
            $courseQueueNumbers[$courseName]++;
            $courseQNum = $courseQueueNumbers[$courseName];
            }

            $dateFiled = $grant->date_filed;

            // Check if applicant, parent, or guardian is JPM (only if user has permission)
            $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
            $rowClass = $isJpm ? 'jpm-row' : '';
            @endphp
            <tr class="{{ $rowClass }}">
                <td style="font-size:10px;min-width:20px;color:#555;padding-left:0.1cm;padding-right:0.1cm;">{{ $overallIndex }}</td>
                <td style="font-size:11px;">
                    <div>{{ $profile->last_name }}, {{ $profile->first_name }}</div>
                    @if(isset($showSequenceNumbers) && $showSequenceNumbers == 1)
                    <div style="font-size:9px;color:#666;margin-top:4px;line-height:1.4;">
                        【Prog.<span style="font-weight: bold;">#{{ $programQNum }}</span> | Sch.<span style="font-weight: bold;">#{{ $schoolQNum }}</span> | Course<span style="font-weight: bold;">#{{ $courseQNum }}</span>】
                        　
                    </div>
                    @endif
                </td>
                <td style="font-size:11px;">
                    @php
                    $contacts = array_filter([
                    $profile->contact_no ?? null,
                    $profile->contact_no_2 ?? null
                    ]);
                    @endphp
                    {{ count($contacts) ? implode(' / ', $contacts) : '-' }}
                </td>
                @if(empty($filters['municipality']) && (!isset($groupBy) || $groupBy !== 'municipality'))
                <td style="font-size:11px;">{{ $profile->municipality ?? '-' }}</td>
                @endif
                @if(empty($filters['program']) && (!isset($groupBy) || $groupBy !== 'program'))
                <td style="font-size:11px;">{{ optional(optional($profile->scholarshipGrant->first())->program)->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['school']) && empty($filters['schools']) && (!isset($groupBy) || $groupBy !== 'school'))
                <td style="font-size:11px;">{{ optional($profile->scholarshipGrant->first())->school->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['course']) && empty($filters['courses']) && (!isset($groupBy) || $groupBy !== 'course'))
                <td style="font-size:10px;">{{ optional($profile->scholarshipGrant->first())->course->name ?? '-' }}</td>
                @endif
                @if(empty($filters['year_level']) && (!isset($groupBy) || $groupBy !== 'year_level'))
                <td style="font-size:11px;">{{ optional($profile->scholarshipGrant->first())->year_level ?? '-' }}</td>
                @endif
                <td style="text-transform:lowercase;font-size:11px;">{{ $profile->remarks ?? '-' }}</td>
                <td style="font-size:11px;">
                    {{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('m/d/Y') : '-' }}
                </td>
            </tr>
            @php $overallIndex++; @endphp
            @endforeach
        </tbody>
    </table>
    @endforeach
    @endif
    <!-- <div class="report-footer">
        <span>Date generated: {{ now()->format('M d, Y h:i A') }}</span>
        <span class="page-number" style="margin-left:2em;"></span>
    </div> -->
</body>

</html>