<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interviewed Applicants Report</title>
    <style>
        body {
            font-family: 'Roboto', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 11px;
            color: #111827;
            padding: 4px 12px;
            margin: 0;
            background: #ffffff;
        }

        h1 {
            font-size: 13px;
            font-weight: 300;
            margin-bottom: 4px;
            color: #111827;
        }

        h2 {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 4px;
            color: #111827;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 3px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        th {
            padding: 4px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            color: #374151;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        td {
            padding: 3px 6px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
            color: #1f2937;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e5e7eb;
        }

        .header-title {
            font-size: 16px;
            font-weight: 300;
            color: #111827;
        }

        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
        }

        .group-title {
            font-size: 12px;
            font-weight: 500;
            color: #1f2937;
            margin: 12px 0 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #d1d5db;
        }

        .summary-box {
            display: inline-block;
            padding: 4px 10px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 4px;
            margin: 2px 4px 2px 0;
            font-size: 10px;
        }

        .summary-section {
            margin-top: 8px;
        }

        .recommendation-recommended {
            color: #15803d;
            font-weight: 600;
        }

        .recommendation-further_evaluation {
            color: #a16207;
            font-weight: 600;
        }

        .recommendation-not_recommended {
            color: #dc2626;
            font-weight: 600;
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
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <img src="data:image/svg+xml;base64,<?php echo $pgpLogoBase64; ?>" alt="PGP Logo" style="height: 50px; width: auto; margin-left: 4px;">
        <div style="flex: 1; text-align: center;">
            <h1 style="margin:0; font-size:11px; font-weight:500; color:#333;">Republic of the Philippines</h1>
            <h1 style="margin:0; font-size:11px; font-weight:500; color:#333;">Provincial Government of Palawan</h1>
            <h1 style="margin:0; font-size:11px; font-weight:600; color:#333;">Akbay sa Mag-Aaral Yaman ng Kinabukasan</h1>
            <h1 style="margin:0; font-size:11px; font-weight:600; color:#333;">(Programang Pang-Edukasyon para sa Palaweño)</h1>
        </div>
        <img src="data:image/svg+xml;base64,<?php echo $yakapLogoBase64; ?>" alt="Yakap Logo" style="height: 50px; width: auto; margin-right: 4px;">
    </div>

    <div class="header">
        <div>
            <div class="header-title" style="font-size: 12px;">
                {{ $reportType === 'list' ? 'Interviewed Applicants Report' : 'Interview Summary Report' }}
            </div>
            <p style="margin: 2px 0 0 0; font-size: 10px; color: #6b7280;">
                {{ $records->count() }} records
            </p>
        </div>
        <div class="header-meta">
            <div>Generated: {{ now()->format('M d, Y h:i A') }}</div>
        </div>
    </div>

    @if($reportType === 'summary')
    <div class="summary-section">
        <h2>Summary by Recommendation</h2>
        @php
        $byRecommendation = $records->groupBy('recommendation');
        $labels = [
        'recommended' => 'Recommended for Approval',
        'further_evaluation' => 'For Further Evaluation',
        'not_recommended' => 'Not Recommended',
        ];
        @endphp
        @foreach($byRecommendation as $rec => $group)
        <div class="summary-box">
            {{ $labels[$rec] ?? $rec }}: <strong>{{ $group->count() }}</strong>
        </div>
        @endforeach
    </div>

    <div class="summary-section">
        <h2>Summary by Program</h2>
        @php $byProgram = $records->groupBy(fn($r) => $r->program->shortname ?? 'N/A'); @endphp
        @foreach($byProgram as $prog => $group)
        <div class="summary-box">
            {{ $prog }}: <strong>{{ $group->count() }}</strong>
        </div>
        @endforeach
    </div>
    @else
    @php
    $grouped = $groupBy !== 'none'
    ? $records->groupBy(function($r) use ($groupBy) {
    return match($groupBy) {
    'program' => $r->program->shortname ?? 'N/A',
    'course' => $r->course->shortname ?? 'N/A',
    'recommendation' => $labels[$r->recommendation] ?? $r->recommendation ?? 'N/A',
    'interviewer' => $r->interviewer->name ?? 'N/A',
    default => 'All',
    };
    })
    : collect(['All' => $records]);

    $labels = [
    'recommended' => 'Recommended for Approval',
    'further_evaluation' => 'For Further Evaluation',
    'not_recommended' => 'Not Recommended',
    ];
    @endphp

    @foreach($grouped as $groupName => $groupRecords)
    @if($groupBy !== 'none')
    <div class="group-title">{{ $groupName }} ({{ $groupRecords->count() }})</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Program</th>
                <th>Course</th>
                @if($includeAssessment)
                <th>Academic</th>
                <th>Financial</th>
                <th>Communication</th>
                @endif
                <th>Recommendation</th>
                <th>Interview Date</th>
                <th>Interviewer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupRecords as $idx => $record)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $record->profile->last_name ?? '' }}, {{ $record->profile->first_name ?? '' }}</td>
                <td>{{ $record->program->shortname ?? 'N/A' }}</td>
                <td>{{ $record->course->shortname ?? 'N/A' }}</td>
                @if($includeAssessment)
                <td>{{ ucfirst($record->academic_potential ?? 'N/A') }}</td>
                <td>{{ ucfirst($record->financial_need_level ?? 'N/A') }}</td>
                <td>{{ ucfirst($record->communication_skills ?? 'N/A') }}</td>
                @endif
                <td class="recommendation-{{ $record->recommendation }}">
                    {{ $labels[$record->recommendation] ?? 'N/A' }}
                </td>
                <td>{{ $record->interviewed_at ? \Carbon\Carbon::parse($record->interviewed_at)->format('M d, Y') : 'N/A' }}</td>
                <td>{{ $record->interviewer->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
    @endif
</body>

</html>