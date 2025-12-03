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

        <!-- Minimalist Report Header -->
        <div class="report-header">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div>
                    <h1 style="margin: 0; font-size: 14px; font-weight: 300; color: #111827;">
                        Selected Applicants
                    </h1>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">
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
                    <th style="width:30px">Seq</th>
                    <th style="width:200px">Name</th>
                    <th style="width:80px">Contact No(s).</th>
                    <th style="width:110px">Municipality</th>
                    <th style="width:85px">Program</th>
                    <th style="width:150px">School</th>
                    <th style="width:130px">Course</th>
                    <th style="width:50px">Level</th>
                    <th style="width:70px">Remarks</th>
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
                $lastDate = null;
                $dateIndex = 1;
                $overallIndex = 1;
                @endphp
                @foreach($sortedProfiles as $profile)
                @php
                $grant = optional($profile->scholarshipGrant->first());
                $dateFiled = $grant->date_filed;

                $dateKey = $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('Y-m-d') : '';
                if ($dateKey !== $lastDate) {
                $dateIndex = 1;
                $lastDate = $dateKey;
                }

                $contacts = array_filter([
                $profile->contact_no ?? null,
                $profile->contact_no_2 ?? null
                ]);

                $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm);
                $rowClass = $isJpm ? 'jpm-row' : '';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td style="font-size:10px;min-width:20px;width:20px;color:#555;padding-left:0.05cm;padding-right:0.05cm;">{{ $overallIndex }}</td>
                    <td style="font-size:11px;">{{ $dateIndex }}</td>
                    <td style="font-size:11px; white-space: pre-line;">{{ strtoupper($profile->last_name) }}, {{ strtoupper($profile->first_name) }}
                        {{ ucfirst(strtolower($profile->middle_name ?? '')) }}
                        【Prog.{{ optional($grant->program)->shortname ?? '-' }} | Sch.{{ optional($grant->school)->shortname ?? '-' }} | {{ optional($grant->course)->name ?? '-' }}】
                    </td>
                    <td style="font-size:11px;">
                        @php
                        $contactsList = array_filter([
                        $profile->contact_no ?? null,
                        $profile->contact_no_2 ?? null
                        ]);
                        @endphp
                        {{ count($contactsList) ? implode(' / ', $contactsList) : '-' }}
                    </td>
                    <td style="font-size:11px;">{{ $profile->municipality ?? '-' }}</td>
                    <td style="font-size:11px;">{{ optional($grant->program)->shortname ?? '-' }}</td>
                    <td style="font-size:11px;">{{ optional($grant->school)->shortname ?? '-' }}</td>
                    <td style="font-size:11px;">{{ optional($grant->course)->name ?? '-' }}</td>
                    <td style="font-size:11px;">{{ $grant->year_level ?? '-' }}</td>
                    <td style="font-size:11px;">{{ $profile->remarks ?? '-' }}</td>
                    <td style="font-size:11px;">{{ $dateFiled ? \Carbon\Carbon::parse($dateFiled)->format('M d, Y') : '-' }}</td>
                </tr>
                @php $dateIndex++; $overallIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    </body>

</html>