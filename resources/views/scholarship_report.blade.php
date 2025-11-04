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
            border-radius: 20px;
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

        /* Status tags */
        .status-tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-auto-approved {
            background: #dbeafe;
            color: #1e40af;
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
        <span>{{ is_array($value) ? implode(', ', array_map('ucwords', str_replace('_', ' ', $value))) : ucwords(str_replace('_', ' ', $value)) }}</span>
        @elseif($key === 'grant_provision')
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
                <h1 style="margin: 0; font-size: 20px; font-weight: 300; color: #111827;">
                    {{ $reportType === 'summary' ? 'Summary Report' : 'Scholarship Report' }}
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
        $showApprovalStatus = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'approval_status');
        $showGrantProvision = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'grant_provision');
        $showProgram = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'program');
        $showSchool = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'school');
        $showCourse = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'course');
        $showYearLevel = (!isset($groupBy) || $groupBy === 'none' || $groupBy === 'year_level');

        $total = $summary['total'] ?? 1;
        @endphp

        @if(isset($summary['by_approval_status']) && $showApprovalStatus)
        <tr>
            <th colspan="3">By Approval Status</th>
        </tr>
        @php
        $statusData = collect($summary['by_approval_status'])->filter(function($count, $name) {
        return $name !== 'No Status';
        })->sortDesc();
        $statusIndex = 1;
        @endphp
        @foreach($statusData as $name => $count)
        <tr>
            <td>{{ $statusIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_approval_status']['No Status']))
        <tr>
            <td><em>{{ $statusIndex }}. No Status</em></td>
            <td>{{ $summary['by_approval_status']['No Status'] }}</td>
            <td>{{ number_format(($summary['by_approval_status']['No Status'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_grant_provision']) && $showGrantProvision)
        <tr>
            <th colspan="3">By Grant Provision</th>
        </tr>
        @php
        $grantData = collect($summary['by_grant_provision'])->filter(function($count, $name) {
        return $name !== 'No Provision';
        })->sortDesc();
        $grantIndex = 1;
        @endphp
        @foreach($grantData as $name => $count)
        <tr>
            <td>{{ $grantIndex++ }}. {{ $name }}</td>
            <td>{{ $count }}</td>
            <td>{{ number_format(($count / $total) * 100, 1) }}%</td>
        </tr>
        @endforeach
        @if(isset($summary['by_grant_provision']['No Provision']))
        <tr>
            <td><em>{{ $grantIndex }}. No Provision</em></td>
            <td>{{ $summary['by_grant_provision']['No Provision'] }}</td>
            <td>{{ number_format(($summary['by_grant_provision']['No Provision'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_program']) && $showProgram)
        <tr>
            <th colspan="3">By Program</th>
        </tr>
        @php
        $programData = collect($summary['by_program'])->filter(function($count, $name) {
        return $name !== 'No Program';
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
        @if(isset($summary['by_program']['No Program']))
        <tr>
            <td><em>{{ $programIndex }}. No Program</em></td>
            <td>{{ $summary['by_program']['No Program'] }}</td>
            <td>{{ number_format(($summary['by_program']['No Program'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_school']) && $showSchool)
        <tr>
            <th colspan="3">By School</th>
        </tr>
        @php
        $schoolData = collect($summary['by_school'])->filter(function($count, $name) {
        return $name !== 'No School';
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
        @if(isset($summary['by_school']['No School']))
        <tr>
            <td><em>{{ $schoolIndex }}. No School</em></td>
            <td>{{ $summary['by_school']['No School'] }}</td>
            <td>{{ number_format(($summary['by_school']['No School'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_course']) && $showCourse)
        <tr>
            <th colspan="3">By Course</th>
        </tr>
        @php
        $courseData = collect($summary['by_course'])->filter(function($count, $name) {
        return $name !== 'No Course';
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
        @if(isset($summary['by_course']['No Course']))
        <tr>
            <td><em>{{ $courseIndex }}. No Course</em></td>
            <td>{{ $summary['by_course']['No Course'] }}</td>
            <td>{{ number_format(($summary['by_course']['No Course'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_year_level']) && $showYearLevel)
        <tr>
            <th colspan="3">By Year Level</th>
        </tr>
        @php
        $yearLevelData = collect($summary['by_year_level'])->filter(function($count, $name) {
        return $name !== 'No Year Level';
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
        @if(isset($summary['by_year_level']['No Year Level']))
        <tr>
            <td><em>{{ $yearLevelIndex }}. No Year Level</em></td>
            <td>{{ $summary['by_year_level']['No Year Level'] }}</td>
            <td>{{ number_format(($summary['by_year_level']['No Year Level'] / $total) * 100, 1) }}%</td>
        </tr>
        @endif
        @endif
    </table>
    @else
    @php
    $profilesToIterate = $groupedProfiles ?? collect(['all' => $profiles]);
    $overallIndex = 1;
    @endphp

    @foreach($profilesToIterate as $groupName => $groupProfiles)
    @php
    $sortedProfiles = $groupProfiles->sortBy(function($profile) {
    $grant = optional($profile->scholarshipGrant->first());
    $approvalStatus = $grant->approval_status ?? '';

    // For approved status, sort alphabetically by school then year level
    if (in_array($approvalStatus, ['approved', 'auto_approved'])) {
    $school = optional($grant->school)->name ?? 'ZZZZ'; // Put empty schools at the end
    $yearLevel = $grant->year_level ?? 'ZZZZ'; // Put empty year levels at the end
    return [$school, $yearLevel, $profile->last_name, $profile->first_name];
    }

    // For other statuses, sort by date filed
    $dateFiled = $grant->date_filed ? \Carbon\Carbon::parse($grant->date_filed)->format('Y-m-d') : '9999-12-31';
    $createdAt = $profile->created_at ? \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';
    return [$dateFiled, $createdAt];
    });
    $groupCount = $sortedProfiles->count();
    @endphp

    @if($groupedProfiles && $groupBy !== 'none')
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

    <table style="@if($groupedProfiles && $groupBy !== 'none') margin-top: 0; @endif @if(!$loop->last) margin-bottom: 20px; @endif table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th style="min-width:20px;width:20px;color:#555;padding-left:0.05cm;padding-right:0.05cm;">#</th>
                <th style="width:180px">Name</th>
                <th style="width:70px">Contact No(s).</th>
                @if(empty($filters['municipality']) && (!isset($groupBy) || $groupBy !== 'municipality'))
                <th style="width:100px">Municipality</th>
                @endif
                @if(empty($filters['program']) && (!isset($groupBy) || $groupBy !== 'program'))
                <th style="width:80px">Program</th>
                @endif
                @if(empty($filters['school']) && (!isset($groupBy) || $groupBy !== 'school'))
                <th style="width:140px">School</th>
                @endif
                @if(empty($filters['course']) && empty($filters['courses']) && (!isset($groupBy) || $groupBy !== 'course'))
                <th style="width:120px">Course</th>
                @endif
                @if(empty($filters['year_level']) && (!isset($groupBy) || $groupBy !== 'year_level'))
                <th style="width:35px">Level</th>
                @endif
                @if(empty($filters['approval_status']) && (!isset($groupBy) || $groupBy !== 'approval_status'))
                <th style="width:80px">Status</th>
                @endif
                @if(empty($filters['grant_provision']) && (!isset($groupBy) || $groupBy !== 'grant_provision'))
                <th style="width:80px">Grant</th>
                @endif
                <th style="width:90px">
                    @php
                    $showingApproved = false;
                    if (isset($filters['approval_status'])) {
                    $statusFilter = is_array($filters['approval_status']) ? $filters['approval_status'] : [$filters['approval_status']];
                    $showingApproved = in_array('approved', $statusFilter) || in_array('auto_approved', $statusFilter);
                    }
                    @endphp
                    {{ $showingApproved ? 'Date Approved' : 'Date Filed' }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($sortedProfiles as $profile)
            @php
            $grant = optional($profile->scholarshipGrant->first());
            $dateFiled = $grant->date_filed;
            $dateApproved = $grant->date_approved;
            $approvalStatus = $grant->approval_status ?? '-';
            $grantProvision = $grant->grant_provision ?? '-';

            $statusClass = '';
            switch($approvalStatus) {
            case 'approved':
            $statusClass = 'status-approved';
            break;
            case 'pending':
            $statusClass = 'status-pending';
            break;
            case 'declined':
            $statusClass = 'status-declined';
            break;
            case 'auto_approved':
            $statusClass = 'status-auto-approved';
            break;
            }

            $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
            $rowClass = $isJpm ? 'jpm-row' : '';

            // Use date approved for approved statuses, otherwise use date filed
            $displayDate = (in_array($approvalStatus, ['approved', 'auto_approved']) && $dateApproved) ? $dateApproved : $dateFiled;
            @endphp
            <tr class="{{ $rowClass }}">
                <td style="font-size:10px;min-width:20px;width:20px;color:#555;padding-left:0.05cm;padding-right:0.05cm;">{{ $overallIndex }}</td>
                <td style="font-size:11px;">{{ $profile->last_name }}, {{ $profile->first_name }} {{ $profile->middle_name }}</td>
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
                <td style="font-size:11px;">{{ strtoupper($profile->municipality ?? '-') }}</td>
                @endif
                @if(empty($filters['program']) && (!isset($groupBy) || $groupBy !== 'program'))
                <td style="font-size:11px;">{{ optional($grant->program)->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['school']) && empty($filters['schools']) && (!isset($groupBy) || $groupBy !== 'school'))
                <td style="font-size:11px;">{{ $grant->school->name ?? '-' }}</td>
                @endif
                @if(empty($filters['course']) && empty($filters['courses']) && (!isset($groupBy) || $groupBy !== 'course'))
                <td style="font-size:10px;">{{ $grant->course->name ?? '-' }}</td>
                @endif
                @if(empty($filters['year_level']) && (!isset($groupBy) || $groupBy !== 'year_level'))
                <td style="font-size:11px;">{{ $grant->year_level ?? '-' }}</td>
                @endif
                @if(empty($filters['approval_status']) && (!isset($groupBy) || $groupBy !== 'approval_status'))
                <td style="font-size:11px;">
                    @if($approvalStatus !== '-')
                    <span class="status-tag {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $approvalStatus)) }}</span>
                    @else
                    -
                    @endif
                </td>
                @endif
                @if(empty($filters['grant_provision']) && (!isset($groupBy) || $groupBy !== 'grant_provision'))
                <td style="font-size:11px;">{{ $grantProvision !== '-' ? ucwords(str_replace('_', ' ', $grantProvision)) : '-' }}</td>
                @endif
                <td style="font-size:11px;">
                    {{ $displayDate ? \Carbon\Carbon::parse($displayDate)->format('m/d/Y') : '-' }}
                </td>
            </tr>
            @php $overallIndex++; @endphp
            @endforeach
        </tbody>
    </table>
    @endforeach
    @endif
</body>

</html>