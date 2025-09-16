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

        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.18rem;
            color: #2563eb;
        }

        h2 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
            color: #1e293b;
        }

        h3 {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 0.09rem;
            color: #334155;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 0.12em;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            border-radius: 0.25rem;
            overflow: hidden;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 0.06rem 0.09rem 0.06rem 0.22rem;
            text-align: left;
            white-space: normal;
            word-break: break-word;
            max-width: 180px;
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

        th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            font-size: 0.95em;
            padding-left: 0.22rem;
        }

        .summary-table {
            margin-bottom: 0.3em;
        }

        .filters {
            margin-bottom: 0.12rem;
            padding: 0.18rem 0.3rem;
            background: #f1f5f9;
            border-radius: 0.375rem;
            color: #333;
            font-size: 0.9rem;
        }

        .badge {
            display: inline-block;
            background: #f1f5f9;
            color: #333;
            border-radius: 0.375rem;
            padding: 0.08rem 0.3rem;
            margin-right: 0.3rem;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <!-- <h1>Report</h1> -->
    <div class="filters">
        <span class="badge">Report Type: {{ ucfirst($reportType) }}</span>
        @foreach($filters as $key => $value)
        @if($value)
        @if($key === 'program' && isset($profiles) && count($profiles) && optional($profiles->first()->scholarshipGrant->first())->program)
        <span class="badge">Program: {{ optional($profiles->first()->scholarshipGrant->first())->program->name }}</span>
        @else
        <span class="badge">{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</span>
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
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_school']))
        <tr>
            <th colspan="2">By School</th>
        </tr>
        @foreach($summary['by_school'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_course']))
        <tr>
            <th colspan="2">By Course</th>
        </tr>
        @foreach($summary['by_course'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
        @if(isset($summary['by_year_level']))
        <tr>
            <th colspan="2">By Year Level</th>
        </tr>
        @foreach($summary['by_year_level'] as $name => $count)
        <tr>
            <td>{{ $name }}</td>
            <td>{{ $count }}</td>
        </tr>
        @endforeach
        @endif
    </table>
    @else
    <h2>Waiting List</h2>
    <table>
        <thead>
            <tr>
                <th style="width:20px;color:#555;padding-left:0.1cm;padding-right:0.1cm;">#</th>
                <th style="width:30px;padding-left:0.05cm;padding-right:0.05cm;">Seq</th>
                <th>Name</th>
                <th>Municipality</th>
                @if(empty($filters['program']))
                <th>Program</th>
                @endif
                <th>School</th>
                <th>Course</th>
                <th style="min-width:40px">Level</th>
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
            @endphp
            <tr>
                <td style="width:20px;color:#555;padding-left:0.1cm;padding-right:0.1cm;">{{ $overallIndex }}</td>
                <td style="padding-left:0.05cm;padding-right:0.05cm;">{{ $dateIndex }}</td>
                <td>{{ $profile->last_name }}, {{ $profile->first_name }}</td>
                <td>{{ $profile->municipality }}</td>
                @if(empty($filters['program']))
                <td>{{ optional(optional($profile->scholarshipGrant->first())->program)->shortname ?? '-' }}</td>
                @endif
                <td>{{ optional($profile->scholarshipGrant->first())->school->shortname ?? '-' }}</td>
                <td>{{ optional($profile->scholarshipGrant->first())->course->shortname ?? '-' }}</td>
                <td>{{ optional($profile->scholarshipGrant->first())->year_level ?? '-' }}</td>
                <td>
                    {{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('M d, Y') : '-' }}
                </td>
            </tr>
            @php $dateIndex++; $overallIndex++; @endphp
            @endforeach
        </tbody>
    </table>
    @endif
    <!-- <div class="report-footer">
        <span>Date generated: {{ now()->format('M d, Y h:i A') }}</span>
        <span class="page-number" style="margin-left:2em;"></span>
    </div> -->
</body>

</html>