<!DOCTYPE html>
<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Report</title>
    <style>
        table {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 13px;
            color: #222;
            padding: 0.03rem 0.25rem 0.03rem 0.18rem;
            margin: 0;
            border-collapse: collapse;
            width: 100%;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #bbbbbb;
            padding: 6px 9px;
            text-align: left;
            white-space: normal;
            word-break: break-word;
        }

        table.data-table th {
            font-weight: 600;
            color: #475569;
        }

        .filters {
            margin-bottom: 12px;
            padding: 10px 15px;
            border-radius: 6px;
            color: #333;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            color: #333;
            border-radius: 6px;
            padding: 4px 12px;
            margin-right: 12px;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
    $profiles = $profiles ?? collect([]);
    $filters = $filters ?? [];
    $reportType = $reportType ?? 'list';
    $summary = $summary ?? null;
    $canViewJpm = $canViewJpm ?? false;
    $includeRemarks = $includeRemarks ?? false;
    $includeGrantProvision = $includeGrantProvision ?? true;
    @endphp

    <!-- Filters -->
    <div class="filters" style="display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; margin-bottom: 0.5rem; font-size: 11px; color: #6b7280;">
        <span>{{ ucfirst($reportType) }}</span>
        @foreach($filters as $key => $value)
        @if($value && !in_array($key, ['paper_size', 'orientation', 'date_filed']))
        <span style="color:#d1d5db;">|</span>
        @if($key === 'unified_status')
        <!-- Display unified status from request parameter -->
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
        <span>Hide JPM</span>
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

    @if($reportType === 'summary')
    <h2>Summary</h2>
    <table class="data-table summary-table">
        <tr>
            <th>Total</th>
            <td>{{ $summary['total'] ?? 0 }}</td>
        </tr>
        @if(isset($summary['by_unified_status']))
        <tr>
            <th colspan="2">By Unified Status</th>
        </tr>
        @foreach($summary['by_unified_status'] as $name => $count)
        @if($name !== 'No Status')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_unified_status']['No Status']))
        <tr>
            <td><em>No Status</em></td>
            <td>{{ $summary['by_unified_status']['No Status'] }}</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_grant_provision']))
        <tr>
            <th colspan="2">By Grant Provision</th>
        </tr>
        @foreach($summary['by_grant_provision'] as $name => $count)
        @if($name !== 'No Provision')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_grant_provision']['No Provision']))
        <tr>
            <td><em>No Provision</em></td>
            <td>{{ $summary['by_grant_provision']['No Provision'] }}</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_program']))
        <tr>
            <th colspan="2">By Program</th>
        </tr>
        @foreach($summary['by_program'] as $name => $count)
        @if($name !== 'No Program')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_program']['No Program']))
        <tr>
            <td><em>No Program</em></td>
            <td>{{ $summary['by_program']['No Program'] }}</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_school']))
        <tr>
            <th colspan="2">By School</th>
        </tr>
        @foreach($summary['by_school'] as $name => $count)
        @if($name !== 'No School')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_school']['No School']))
        <tr>
            <td><em>No School</em></td>
            <td>{{ $summary['by_school']['No School'] }}</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_course']))
        <tr>
            <th colspan="2">By Course</th>
        </tr>
        @foreach($summary['by_course'] as $name => $count)
        @if($name !== 'No Course')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_course']['No Course']))
        <tr>
            <td><em>No Course</em></td>
            <td>{{ $summary['by_course']['No Course'] }}</td>
        </tr>
        @endif
        @endif

        @if(isset($summary['by_year_level']))
        <tr>
            <th colspan="2">By Year Level</th>
        </tr>
        @foreach($summary['by_year_level'] as $name => $count)
        @if($name !== 'No Year Level')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_year_level']['No Year Level']))
        <tr>
            <td><em>No Year Level</em></td>
            <td>{{ $summary['by_year_level']['No Year Level'] }}</td>
        </tr>
        @endif
        @endif
    </table>
    @else
    @php
    $colCount = 5; // Base columns: #, Name, Contact No(s)., Date = 4 + 1 more
    if(empty($filters['municipality'])) $colCount++;
    if(empty($filters['program'])) $colCount++;
    if(empty($filters['school'])) $colCount++;
    if(empty($filters['course'])) $colCount++;
    if(empty($filters['year_level'])) $colCount++;
    if(empty($filters['approval_status'])) $colCount++; // Check request parameter
    if(empty($filters['grant_provision'])) $colCount++;
    if($includeGrantProvision ?? false) $colCount++;
    if($includeRemarks ?? false) $colCount++;

    $showingApproved = false;
    if (isset($filters['approval_status'])) {
    $statusFilter = is_array($filters['approval_status']) ? $filters['approval_status'] : [$filters['approval_status']];
    $showingApproved = in_array('approved', $statusFilter) || in_array('auto_approved', $statusFilter);
    }
    @endphp
    <table style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th colspan="{{ $colCount }}" style="text-align: center;">
                    <h1 style="margin:0; font-size:14px; font-weight:500; color:#333;">Republic of the Philippines</h1>
                </th>
            </tr>
            <tr>
                <th colspan="{{ $colCount }}" style="text-align: center;">
                    <h1 style="margin:0; font-size:14px; font-weight:500; color:#333;">Provincial Government of Palawan</h1>
                </th>
            </tr>
            <tr>
                <th colspan="{{ $colCount }}" style="text-align: center;">
                    <h1 style="margin:0; font-size:14px; font-weight:600; color:#333;">Akbay sa Mag-Aaral Yaman ng Kinabukasan</h1>
                </th>
            </tr>
            <tr>
                <th colspan="{{ $colCount }}" style="text-align: center;">
                    <h1 style="margin:0; font-size:14px; font-weight:600; color:#333;">(Programang Pang-Edukasyon para sa Palaweño)</h1>
                </th>
            </tr>
        </thead>
    </table>
    @php
    $exStatusFilter = $filters['unified_status'] ?? null;
    $exSingleStatus = is_array($exStatusFilter) ? (count($exStatusFilter) === 1 ? $exStatusFilter[0] : null) : $exStatusFilter;
    $exPersonLabel = in_array($exSingleStatus, ['pending', 'denied']) ? 'Applicants' : 'Scholars';
    $exStatusLabel = $exSingleStatus ? ucfirst($exSingleStatus) : null;
    $exReportTitle = ($reportType === 'summary')
    ? ($exStatusLabel ? 'Summary Report of ' . $exStatusLabel . ' ' . $exPersonLabel : 'Summary Report')
    : ($exStatusLabel ? 'Detailed List of ' . $exStatusLabel . ' ' . $exPersonLabel : 'Detailed List');
    @endphp
    <table class="data-table" border="1" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th colspan="{{ $colCount }}">
                    <h2>{{ $exReportTitle }}</h2>
                </th>
            </tr>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 210px;">Name</th>
                <th style="width: 100px;">Contact No(s).</th>
                @if(empty($filters['municipality']))
                <th style="width: 130px;">Municipality</th>
                @endif
                @if(empty($filters['program']))
                <th style="width: 90px;">Program</th>
                @endif
                @if(empty($filters['school']))
                <th style="width: 170px;">School</th>
                @endif
                @if(empty($filters['course']))
                <th style="width: 150px;">Course</th>
                @endif
                @if(empty($filters['year_level']))
                <th style="width: 50px;">Level</th>
                @endif
                @if(empty($filters['approval_status']))
                <th style="width: 110px;">Status</th>
                @endif
                @if($includeGrantProvision ?? false)
                <th style="width: 100px;">Grant Provision</th>
                @endif
                @if($includeRemarks ?? false)
                <th>Remarks</th>
                @endif
                <th style="width: 70px;">{{ $showingApproved ? 'Date Approved' : 'Date Filed' }}</th>
            </tr>
        </thead>
        <tbody>
            @php
            $sortedProfiles = isset($profiles) && $profiles ? $profiles->sortBy(function($profile) {
            $grant = optional($profile->scholarshipGrant->first());
            $unifiedStatus = $grant->unified_status ?? '';

            // For approved/active status, sort alphabetically by school then year level
            if (in_array($unifiedStatus, ['approved', 'active'])) {
            $school = optional($grant->school)->name ?? 'ZZZZ';
            $yearLevel = $grant->year_level ?? 'ZZZZ';
            return [$school, $yearLevel, $profile->last_name, $profile->first_name];
            }

            // For other statuses, sort by date filed
            $dateFiled = $grant->date_filed ?? '9999-12-31';
            $createdAt = $profile->created_at ?? '9999-12-31 23:59:59';
            return [$dateFiled, $createdAt];
            }) : collect([]);
            $overallIndex = 1;
            @endphp
            @foreach($sortedProfiles as $profile)
            @php
            $grant = optional($profile->scholarshipGrant->first());
            $dateFiled = $grant->date_filed;
            $dateApproved = $grant->date_approved;
            $unifiedStatus = $grant->unified_status ?? '-';
            $grantProvision = $grant->grant_provision ?? '-';
            $remarks = $profile->remarks ?? $grant->remarks ?? ($grant->scholarshipStatus?->remarks ?? '-');

            $contacts = array_filter([
            $profile->contact_no ?? null,
            $profile->contact_no_2 ?? null
            ]);

            // Use date approved for approved statuses, otherwise use date filed
            $displayDate = (in_array($unifiedStatus, ['approved', 'active']) && $dateApproved) ? $dateApproved : $dateFiled;

            // Check if applicant, parent, or guardian is JPM
            $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
            @endphp
            <tr @if($isJpm) style="background-color: #d1fae5;" @endif>
                <td>{{ $overallIndex }}</td>
                <td>{{ $profile->last_name }}, {{ $profile->first_name }} {{ $profile->middle_name }}</td>
                <td>{{ count($contacts) ? implode(' / ', $contacts) : '-' }}</td>
                @if(empty($filters['municipality']))
                <td>{{ strtoupper($profile->municipality ?? '-') }}</td>
                @endif
                @if(empty($filters['program']))
                <td>{{ optional($grant->program)->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['school']))
                <td>{{ optional($grant)->school ? optional($grant->school)->name : '-' }}</td>
                @endif
                @if(empty($filters['course']))
                <td>{{ optional($grant)->course ? optional($grant->course)->name : '-' }}</td>
                @endif
                @if(empty($filters['year_level']))
                <td>{{ $grant->year_level ?? '-' }}</td>
                @endif
                @if(empty($filters['approval_status']))
                <td>{{ $unifiedStatus !== '-' ? ucwords(str_replace('_', ' ', $unifiedStatus)) : '-' }}</td>
                @endif
                @if($includeGrantProvision ?? false)
                <td>{{ $grantProvision !== '-' ? ucwords(str_replace('_', ' ', $grantProvision)) : '-' }}</td>
                @endif
                @if($includeRemarks ?? false)
                <td>{{ strip_tags($remarks) }}</td>
                @endif
                <td>{{ $displayDate ? \Carbon\Carbon::parse($displayDate)->format('M d, Y') : '-' }}</td>
            </tr>
            @php $overallIndex++; @endphp
            @endforeach
        </tbody>
    </table>
    @endif
</body>

</html>