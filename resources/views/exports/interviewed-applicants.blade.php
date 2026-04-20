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
            table-layout: fixed;
            margin-top: 3px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        th {
            padding: 3px 4px;
            text-align: left;
            font-weight: 600;
            font-size: 8px;
            line-height: 1.15;
            color: #374151;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        td {
            padding: 3px 4px;
            border: 1px solid #e5e7eb;
            font-size: 8px;
            line-height: 1.2;
            color: #1f2937;
            word-break: break-word;
            overflow-wrap: anywhere;
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

        .summary-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
        }

        .summary-card {
            border: 1px solid #d1d5db;
        }

        .summary-card-title {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #111827;
        }

        .summary-card table {
            margin-top: 0;
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
            <p style="margin: 2px 0 0 0; font-size: 10px; color: #6b7280;">
                Total Projected Expense: PHP {{ number_format($records->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}
            </p>
        </div>
        <div class="header-meta">
            <div>Generated: {{ now()->format('M d, Y h:i A') }}</div>
        </div>
    </div>

    @if($reportType === 'summary')
    @php
    $labels = [
    'recommended' => 'Recommended for Approval',
    'further_evaluation' => 'For Further Evaluation',
    'not_recommended' => 'Not Recommended',
    ];
    $recommendationOrder = ['recommended', 'further_evaluation', 'not_recommended', 'unknown'];
    $byRecommendation = $records->groupBy('recommendation')
    ->sortBy(function ($group, $key) use ($recommendationOrder) {
    $index = array_search($key, $recommendationOrder, true);
    return $index === false ? 999 : $index;
    });
    $byProgram = $records->groupBy(fn($r) => $r->program->shortname ?? 'N/A')->sortKeys();
    @endphp

    <div class="summary-stack">
        <div class="summary-card">
            <div class="summary-card-title">By Recommendation</div>
            <table>
                <thead>
                    <tr>
                        <th>Recommendation</th>
                        <th style="text-align:right;">Interviewed</th>
                        <th style="text-align:right;">Projected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byRecommendation as $rec => $group)
                    <tr>
                        <td>{{ $labels[$rec] ?? $rec ?? 'N/A' }}</td>
                        <td style="text-align:right;font-weight:700;">{{ $group->count() }}</td>
                        <td style="text-align:right;font-weight:700;">
                            PHP {{ number_format($group->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-card">
            <div class="summary-card-title">By Program</div>
            <table>
                <thead>
                    <tr>
                        <th>Program</th>
                        <th style="text-align:right;">Interviewed</th>
                        <th style="text-align:right;">Projected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byProgram as $prog => $group)
                    <tr>
                        <td>{{ $prog }}</td>
                        <td style="text-align:right;font-weight:700;">{{ $group->count() }}</td>
                        <td style="text-align:right;font-weight:700;">
                            PHP {{ number_format($group->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-section">
            <div class="summary-box">
                Overall Projected Total:
                <strong>PHP {{ number_format($records->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}</strong>
            </div>
        </div>
    </div>
    @else
    @php
    $grouped = $groupBy !== 'none'
    ? $records->groupBy(function($r) use ($groupBy) {
    return match($groupBy) {
    'program' => $r->program->shortname ?? 'N/A',
    'course' => $r->course->name ?? $r->course->shortname ?? 'N/A',
    'recommendation' => $labels[$r->recommendation] ?? $r->recommendation ?? 'N/A',
    'interviewer' => $r->interviewer->name ?? 'N/A',
    default => 'All',
    };
    })
    : collect(['All' => $records]);

    @endphp

    @foreach($grouped as $groupName => $groupRecords)
    @if($groupBy !== 'none')
    <div class="group-title">
        {{ $groupName }} ({{ $groupRecords->count() }})
        - Projected Total: PHP {{ number_format($groupRecords->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: middle;">#</th>
                <th rowspan="2" style="width: 13%; vertical-align: middle;">Name</th>
                <th rowspan="2" style="width: 7%; vertical-align: middle;">Program</th>
                <th rowspan="2" style="width: 18%; vertical-align: middle;">Course</th>
                <th rowspan="2" style="width: 7%; vertical-align: middle;">Year</th>
                <th rowspan="2" style="width: 10%; vertical-align: middle;">Semester</th>
                <th rowspan="2" style="width: 10%; vertical-align: middle;">Grant</th>
                <th colspan="3" style="text-align: center;">Projected</th>
                <th colspan="2" style="text-align: center;">Interview</th>
            </tr>
            <tr>
                <th style="width: 7%;">Terms</th>
                <th style="width: 11%;">Expense</th>
                <th style="width: 7%;">Completion</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 9%;">By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupRecords as $idx => $record)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $record->profile->last_name ?? '' }}, {{ $record->profile->first_name ?? '' }}</td>
                <td>{{ $record->program->shortname ?? 'N/A' }}</td>
                <td>{{ $record->course->name ?? $record->course->shortname ?? 'N/A' }}</td>
                <td>{{ $record->year_level ?? 'N/A' }}</td>
                <td>{{ $record->term ?? 'N/A' }}</td>
                <td>{{ $record->grant_provision_label ?? \App\Models\SystemOption::formatGrantProvisionLabel($record->grant_provision, 'N/A') }}</td>
                <td>{{ $record->projected_term_count ?? 'Not configured' }}</td>
                <td>
                    @if($record->projected_total_expense !== null)
                    PHP {{ number_format((float) $record->projected_total_expense, 2) }}
                    @else
                    Not configured
                    @endif
                </td>
                <td>{{ $record->projected_completion_year ?? 'Not configured' }}</td>
                <td>{{ $record->interviewed_at ? \Carbon\Carbon::parse($record->interviewed_at)->format('M d, Y') : 'N/A' }}</td>
                <td>{{ $record->interviewer->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach

    <div style="margin-top: 8px; text-align: right; font-size: 10px; font-weight: 600; color: #374151;">
        Overall Projected Total: PHP {{ number_format($records->sum(fn($record) => (float) ($record->projected_total_expense ?? 0)), 2) }}
    </div>
    @endif
</body>

</html>