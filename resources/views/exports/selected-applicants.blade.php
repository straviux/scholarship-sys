<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selected Applicants Report</title>
    <style>
        /* Minimalist Material Design */
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
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 4px;
            color: #111827;
        }

        h3 {
            font-size: 11px;
            font-weight: 400;
            margin-bottom: 3px;
            color: #374151;
        }

        /* Minimalist Tables */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 3px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        th,
        td {
            border: none;
            border-bottom: 1px solid #f3f4f6;
            padding: 3px 4px;
            text-align: left;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: top;
            font-size: 10px;
        }

        /* Minimalist Table Header */
        th {
            background: #f9fafb;
            font-weight: 500;
            color: #374151;
            font-size: 10px;
            padding: 3px 4px;
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
            margin-bottom: 4px;
            padding: 4px 6px;
            background: #eff6ff;
            border-radius: 6px;
            border: 1px solid #dbeafe;
            color: #1e40af;
            font-size: 10px;
        }

        .badge {
            display: inline-block;
            background: #ffffff;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 1px 5px;
            margin-right: 5px;
            font-size: 9px;
            font-weight: 400;
        }

        /* Minimalist School Header */
        .school-header {
            border-bottom: 2px solid #d1d5db;
            padding: 4px 0;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        /* Minimalist Report Header */
        .report-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 8px;
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
            color: #0c4a6e;
        }
    </style>
</head>

<body>

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

        <!-- Minimalist Report Header -->
        <div class="report-header">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div>
                    <h1 style="margin: 0; font-size: 12px; font-weight: 300; color: #111827;">
                        Selected Applicants
                    </h1>
                    <p style="margin: 2px 0 0 0; font-size: 10px; color: #6b7280;">
                        @php
                        $profiles = $profiles ?? collect([]);
                        $canViewJpm = $canViewJpm ?? false;
                        @endphp
                        {{ $profiles->count() }} records
                    </p>
                </div>
            </div>
        </div>

        <table style="table-layout: fixed; width: 100%;">
            <thead>
                <tr>
                    <th style="min-width:20px;width:20px;color:#555;padding-left:0.05cm;padding-right:0.05cm;">#</th>
                    <th style="width:200px">Name</th>
                    <th style="width:140px">Municipality & Contact</th>
                    <th style="width:60px">Program</th>
                    <th style="width:60px">School</th>
                    <th style="width:70px">Course</th>
                    <th style="width:40px">Level</th>
                    <th style="width:180px">Remarks</th>
                    <th style="width:70px">Date Filed</th>
                </tr>
            </thead>
            <tbody>
                @php
                $profiles = $profiles ?? collect([]);
                $sortedProfiles = $profiles->sortBy(function($profile) {
                $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
                return [$dateFiled, $profile->created_at];
                });

                // Initialize sequence counters for program, school, and course
                $programSequences = [];
                $schoolSequences = [];
                $courseSequences = [];

                $overallIndex = 1;
                @endphp
                @foreach($sortedProfiles as $profile)
                @php
                $grant = optional($profile->scholarshipGrant->first());
                $dateFiled = $grant->date_filed;

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

                $contacts = array_filter([
                $profile->contact_no ?? null,
                $profile->contact_no_2 ?? null
                ]);

                $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
                $rowClass = $isJpm ? 'jpm-row' : '';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td style="font-size:10px;min-width:18px;width:18px;color:#555;padding:2px 3px;">{{ $overallIndex }}</td>
                    <td style="font-size:10px; white-space: pre-line;">{{ strtoupper($profile->last_name) }}, {{ strtoupper($profile->first_name) }}
                        {{ ucfirst(strtolower($profile->middle_name ?? '')) }}
                        <div style="font-size:9px; color:#666; margin-top:1px;">
                            Prog.#{{ $programSeqNum }} | Sch.#{{ $schoolSeqNum }} | Course#{{ $courseSeqNum }}
                        </div>
                    </td>
                    <td style="font-size:10px;">
                        <div>{{ $profile->municipality ?? '-' }}</div>
                        <div style="font-size:9px; color:#666;">
                            @php
                            $contactsList = array_filter([
                            $profile->contact_no ?? null,
                            $profile->contact_no_2 ?? null
                            ]);
                            @endphp
                            {{ count($contactsList) ? implode(' / ', $contactsList) : '-' }}
                        </div>
                    </td>
                    <td style="font-size:10px;">{{ optional($grant->program)->shortname ?? '-' }}</td>
                    <td style="font-size:10px;">{{ optional($grant->school)->shortname ?? '-' }}</td>
                    <td style="font-size:10px;">{{ optional($grant->course)->shortname ?? optional($grant->course)->name ?? '-' }}</td>
                    <td style="font-size:10px;">{{ $grant->year_level ?? '-' }}</td>
                    <td style="font-size:10px;">{{ $profile->remarks ?? '-' }}</td>
                    <td style="font-size:10px;">{{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('M d, Y') : '-' }}</td>
                </tr>
                @php $overallIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    </body>

</html>