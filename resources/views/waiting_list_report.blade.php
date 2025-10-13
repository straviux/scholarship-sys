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
            color: #333;
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
            border: 1px solid #bbbbbb;
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
    <h3>Summary</h3>
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
    <h3>Waiting List</h3>
    <table>
        <thead>
            <tr>
                <th style="min-width:30px;color:#555;padding-left:0.1cm;padding-right:0.1cm;">#</th>
                <th style="width:30px;padding-left:0.05cm;padding-right:0.05cm;">Q#</th>
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
                <th style="width:35px">Level</th>
                @endif
                <th>Remarks</th>
                <th style="width:68px">Date Filed (mm/dd/Y)</th>
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
            // Check if applicant, parent, or guardian is JPM (only if user has permission)
            $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
            $bgStyle = $isJpm ? 'background-color: #d1fae5 !important;' : '';
            @endphp
            <tr>
                <td style="min-width:20px;color:#555;padding-left:0.1cm;padding-right:0.1cm;{{ $bgStyle }}">{{ $overallIndex }}</td>
                <td style="padding-left:0.05cm;padding-right:0.05cm;{{ $bgStyle }}">{{ $dateIndex }}</td>
                <td style="font-size:12px;{{ $bgStyle }}">{{ $profile->last_name }}, {{ $profile->first_name }}</td>
                <td style="font-size:12px;{{ $bgStyle }}">
                    @php
                    $contacts = array_filter([
                    $profile->contact_no ?? null,
                    $profile->contact_no_2 ?? null
                    ]);
                    @endphp
                    {{ count($contacts) ? implode(' / ', $contacts) : '-' }}
                </td>
                @if(empty($filters['municipality']))
                <td style="font-size:12px;{{ $bgStyle }}">{{ $profile->municipality ?? '-' }}</td>
                @endif
                @if(empty($filters['program']))
                <td style="font-size:12px;{{ $bgStyle }}">{{ optional(optional($profile->scholarshipGrant->first())->program)->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['school']))
                <td style="font-size:12px;{{ $bgStyle }}">{{ optional($profile->scholarshipGrant->first())->school->shortname ?? '-' }}</td>
                @endif
                @if(empty($filters['course']))
                <td style="font-size:12px;{{ $bgStyle }}">{{ optional($profile->scholarshipGrant->first())->course->name ?? '-' }}</td>
                @endif
                @if(empty($filters['year_level']))
                <td style="font-size:12px;{{ $bgStyle }}">{{ optional($profile->scholarshipGrant->first())->year_level ?? '-' }}</td>
                @endif
                <td style="text-transform:lowercase;font-size:11px;{{ $bgStyle }}">{{ $profile->remarks ?? '-' }}</td>
                <td style="font-size:12px;{{ $bgStyle }}">
                    {{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('m/d/Y') : '-' }}
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