<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Report</title>
    <style>
        /* Only keep the second definition below for .report-footer and @media print */

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 13px;
            color: #222;
            padding: 0.03rem 0.25rem 0.03rem 0.18rem;
            margin: 0;
        }

        .jpm-highlight {
            background-color: #fef3c7 !important;
            border-left: 3px solid #f59e0b !important;
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

    <div class="filters" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; justify-content: flex-start; margin-bottom: 1rem; background: #f1f5f9;">
        <span class="badge" style="color:#444;">Report type: {{ ucfirst($reportType) }}</span>
        @foreach($filters as $key => $value)
        @if($value)
        <span style="font-size:1.5em;color:#c2c2c2;vertical-align:middle;margin:0 0.2em;">|</span>
        @if($key === 'program' && isset($profiles) && count($profiles) && optional($profiles->first()->scholarshipGrant->first())->program)
        <span class="badge" style="color:#444;">Program: {{ optional($profiles->first()->scholarshipGrant->first())->program->name }}</span>
        @else
        <span class="badge" style="color:#444;">{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
        @endif
        @endif
        @endforeach
    </div>
    @if($reportType === 'summary')
    <h2>Summary</h2>
    <table class="summary-table">
        <tr>
            <th>Total</th>
            <td>{{ $summary['total'] ?? 0 }}</td>
        </tr>
        @if(isset($summary['by_program']))
        <tr>
            <th colspan="2">By Program</th>
        </tr>
        @foreach($summary['by_program'] as $name => $count)
        @if($name !== 'no_program')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_program']['no_program']))
        <tr>
            <td><em>No Program</em></td>
            <td>{{ $summary['by_program']['no_program'] }}</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_school']))
        <tr>
            <th colspan="2">By School</th>
        </tr>
        @foreach($summary['by_school'] as $name => $count)
        @if($name !== 'no_school')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_school']['no_school']))
        <tr>
            <td><em>No School</em></td>
            <td>{{ $summary['by_school']['no_school'] }}</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_course']))
        <tr>
            <th colspan="2">By Course</th>
        </tr>
        @foreach($summary['by_course'] as $name => $count)
        @if($name !== 'no_course')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_course']['no_course']))
        <tr>
            <td><em>No Course</em></td>
            <td>{{ $summary['by_course']['no_course'] }}</td>
        </tr>
        @endif
        @endif
        @if(isset($summary['by_year_level']))
        <tr>
            <th colspan="2">By Year Level</th>
        </tr>
        @foreach($summary['by_year_level'] as $name => $count)
        @if($name !== 'no_year_level')
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endif
        @endforeach
        @if(isset($summary['by_year_level']['no_year_level']))
        <tr>
            <td><em>No Year Level</em></td>
            <td>{{ $summary['by_year_level']['no_year_level'] }}</td>
        </tr>
        @endif
        @endif
    </table>
    @else
    @php
    $colCount = 6; // Updated from 5 to 6 to include remarks column
    if(empty($filters['municipality'])) $colCount++;
    if(empty($filters['program'])) $colCount++;
    if(empty($filters['school'])) $colCount++;
    if(empty($filters['course'])) $colCount++;
    if(empty($filters['year_level'])) $colCount++;
    @endphp
    <table border="1">
        <thead>
            <tr>
                <th colspan="{{ $colCount }}">
                    <h2>Waiting List</h2>
                </th>
            </tr>
            <tr>
                <th>#</th>
                <th>Seq</th>
                <th>Name</th>
                <th>Contact No(s).</th>
                @if(empty($filters['municipality']))
                <th>Municipality</th>
                @endif
                @if(empty($filters['program']))
                <th>Program</th>
                @endif
                @if(empty($filters['school']))
                <th>School</th>
                @endif
                @if(empty($filters['course']))
                <th>Course</th>
                @endif
                @if(empty($filters['year_level']))
                <th>Level</th>
                @endif
                <th>Remarks</th>
                <th>Date Filed</th>
            </tr>
        </thead>
        <tbody>
            @php
            $sortedProfiles = $profiles->sortBy(function($profile) {
            $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
            return [$dateFiled, $profile->created_at];
            });
            $lastDate = null;
            $dateIndex = 1;
            $overallIndex = 1;
            @endphp
            @foreach($sortedProfiles as $profile)
            @php
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
            // Check if applicant, parent, or guardian is JPM
            $isJpm = $profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm;
            @endphp
            <tr{{ $isJpm ? ' class="jpm-highlight"' : '' }}>
                <td>{{ $overallIndex }}</td>
                <td>{{ $dateIndex }}</td>
                <td>{{ $profile->last_name }}, {{ $profile->first_name }}</td>
                <td>{{ count($contacts) ? implode(' / ', $contacts) : '-' }}</td>
                @if(empty($filters['municipality']))
                <td>{{ $profile->municipality ?? '-' }}</td>
                @endif
                @if(empty($filters['program']))
                <td>{{ optional(optional($profile->scholarshipGrant->first())->program)->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['school']))
                <td>{{ optional($profile->scholarshipGrant->first())->school->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['course']))
                <td>{{ optional($profile->scholarshipGrant->first())->course->name ?? '-' }}</td>
                @endif
                @if(empty($filters['year_level']))
                <td>{{ optional($profile->scholarshipGrant->first())->year_level ?? '-' }}</td>
                @endif
                <td>{{ $profile->remarks ?? '-' }}</td>
                <td>{{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('M d, Y') : '-' }}</td>
                </tr>
                @php $dateIndex++; $overallIndex++; @endphp
                @endforeach
                {{-- Example summary/footer row: --}}
                {{-- <tr><td colspan="{{ $colCount }}">Summary or footer here</td>
                </tr> --}}
        </tbody>
    </table>
    @endif
    <!-- <div class="report-footer">
        <span>Date generated: {{ now()->format('M d, Y h:i A') }}</span>
        <span class="page-number" style="margin-left:2em;"></span>
    </div> -->
</body>

</html>