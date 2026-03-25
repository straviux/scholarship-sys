<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Ledger - {{ $profile->last_name }}, {{ $profile->first_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        @page {
            size: 8.5in 13in;
            margin: 10mm 12mm;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                box-shadow: none;
            }
        }

        p {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.4;
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
            background: #fff;
        }

        /* ── Header ─────────────────────────────────── */
        .header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 0;
        }

        .header-logo {
            position: absolute;
            left: 16%;
            width: 64px;
            height: 64px;
        }

        .header-logo img {
            width: 100%;
            height: auto;
        }

        .header-text {
            text-align: center;
            flex: 1;
        }

        .header-text p.main {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
        }

        .header-text p.sub {
            font-size: 12px;
            font-weight: 400;
        }

        /* ── Document title band ─────────────────────── */
        .doc-title {
            text-align: center;
            padding: 5px 0;
        }

        .doc-title p.title-main {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .doc-title p.title-sub {
            font-size: 11px;
        }

        /* ── Scholar info band ───────────────────────── */
        .scholar-info {
            padding: 6px 0 8px;
        }

        .info-columns {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }

        .info-col-left {
            flex: 2.2;
        }

        .info-col-right {
            flex: 1;
        }

        .info-table {
            border-collapse: collapse;
            width: 100%;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .info-lbl {
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: tight;
            white-space: nowrap;
            padding-right: 4px;
        }

        .info-val {
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
            padding-left: 12px !important;
        }

        /* ── Ledger table ─────────────────────────────── */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 10px;
        }

        .ledger-table th {
            color: #333;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: center;
            padding: 5px 4px;
            border: 1px solid #000;
        }

        .ledger-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
            text-align: center;
        }

        .ledger-table td.coverage-cell {
            font-weight: 700;
            text-transform: uppercase;
        }

        .ledger-table td.amount-cell {
            text-align: center;
            font-weight: 600;
            color: #333;
        }

        .ledger-table tr.total-row td {
            font-weight: 700;
            border-top: 1px solid #000;
        }

        .ledger-table tr.pre-total-row td {
            border-bottom: none;
        }

        /* ── Signatories ───────────────────────────── */
        .signatories {
            display: flex;
            margin-top: 28px;
            gap: 24px;
        }

        .signatory-block {
            min-width: 200px;
        }

        .signatory-label {
            font-size: 10px;
            color: #333;
            margin-bottom: 20px !important;
        }

        .signatory-name {
            padding-top: 48px !important;
            font-size: 11px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .signatory-designation {
            font-size: 10px;
            color: #333;
            text-transform: uppercase;
        }

        /* ── Content placeholder ─────────────────────── */
        .content-area {
            min-height: 600px;
            padding: 16px;
        }

        /* ── Utility ─────────────────────────────────── */
        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: 700;
        }

        .mt-2 {
            margin-top: 6px !important;
        }
    </style>
</head>

<body>
    <?php
    $logoPath   = public_path('images/pgp-logo.svg');
    $logoDataUrl = null;
    if (file_exists($logoPath)) {
        $logoBase64  = base64_encode(file_get_contents($logoPath));
        $logoDataUrl = 'data:image/svg+xml;base64,' . $logoBase64;
    }

    // All active scholarship records
    $allRecords = $profile->scholarshipGrant->whereNull('deleted_at');

    // Latest active scholarship record
    $latestRecord = $allRecords->sortByDesc('created_at')->first();

    // Earliest record (scholarship start)
    $earliestRecord = $allRecords->sortBy('created_at')->first();

    // Scholarship coverage string — based on actual disbursement terms
    $coverageStr = 'N/A';
    if ($disbBaseline = $profile->disbursements->whereNull('deleted_at')) {
        $uniqueTerms = $disbBaseline->unique(fn($d) => ($d->year_level ?? '') . '|' . ($d->semester ?? '') . '|' . ($d->academic_year ?? ''));
        if ($uniqueTerms->count() > 0) {
            $totalMonths = 0;
            foreach ($uniqueTerms as $d) {
                $isTrimester = str_contains(strtolower($d->semester ?? ''), 'trimester');
                $totalMonths += $isTrimester ? 4 : 6;
            }
            $yrs    = $totalMonths / 12;
            $yrsStr = ($yrs == floor($yrs))
                ? intval($yrs) . ' YRS'
                : number_format($yrs, 1) . ' YRS';
            $firstD = $disbBaseline->sortBy('date_obligated')->first();
            $coverageStr = strtoupper($firstD->year_level ?? '')
                . ', ' . strtoupper($firstD->semester ?? '')
                . ', ' . ($firstD->academic_year ?? '')
                . ' (' . $yrsStr . ')';
        }
    }

    // Age from birthdate
    $age = $profile->birthdate
        ? \Carbon\Carbon::parse($profile->birthdate)->diffInYears(\Carbon\Carbon::now()) . ' yrs old'
        : '';

    // Permanent address
    $addrParts = array_filter([
        $profile->address      ?? null,
        $profile->barangay     ?? null,
        $profile->municipality ?? null,
    ]);
    $permanentAddress = count($addrParts) ? implode(', ', $addrParts) : '—';
    ?>

    <div class="container">

        <!-- ── Header ────────────────────────────────── -->
        <div class="header">
            @if($logoDataUrl)
            <div class="header-logo">
                <img src="{{ $logoDataUrl }}" alt="PGP Logo">
            </div>
            @endif
            <div class="header-text">
                <p class="sub">Republic of the Philippines</p>
                <p class="sub">Provincial Government of Palawan</p>
                <p class="main">Akbay sa Mag-aaral Yaman ng Kinabukasan</p>
                <p style="font-style: italic;">(Programang Pang-Edukasyon para sa Palawenyo)</p>
            </div>
        </div>

        <!-- ── Document Title ────────────────────────── -->
        <div class="header-text" style="margin-top: 12pt;">
            <p class="main">INDIVIDUAL SCHOLAR LEDGER</p>
        </div>

        <!-- ── Scholar Information Band ─────────────── -->
        <div class="scholar-info" style="margin-top: 10pt;">
            <div class="info-columns">

                <!-- Left column -->
                <div class="info-col-left">
                    <table class="info-table">
                        <tr>
                            <td class="info-lbl">NAME</td>
                            <td class="info-val">
                                {{ strtoupper($profile->last_name) }},
                                {{ strtoupper($profile->first_name) }}
                                {{ $profile->middle_name ? strtoupper($profile->middle_name) : '' }}
                                {{ $profile->extension_name ? strtoupper($profile->extension_name) : '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="info-lbl">BIRTHDATE</td>
                            <td class="info-val">
                                {{ $profile->birthdate ? strtoupper(\Carbon\Carbon::parse($profile->birthdate)->format('F d, Y')) : '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="info-lbl">CIVIL STATUS</td>
                            <td class="info-val">{{ $profile->civil_status ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">DEGREE PROGRAM</td>
                            <td class="info-val">{{ $latestRecord?->course?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">PERMANENT ADDRESS</td>
                            <td class="info-val">{{ $permanentAddress }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">SCHOLARSHIP COVERAGE</td>
                            <td class="info-val">{{ $coverageStr }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">OTHER ASSISTANCE</td>
                            <td class="info-val">N/A</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">LICENSURE EXAMINATION</td>
                            <td class="info-val">N/A</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">RESULT</td>
                            <td class="info-val">N/A</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">SCHOOL ATTENDED</td>
                            <td class="info-val">{{ $latestRecord?->school_name ?? ($latestRecord?->school?->name ?? '—') }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Right column -->
                <div class="info-col-right">
                    <table class="info-table">
                        <tr>
                            <td class="info-lbl">GENDER</td>
                            <td class="info-val">{{ $profile->gender ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">AGE</td>
                            <td class="info-val">{{ $age ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="info-lbl">CONTACT NO.</td>
                            <td class="info-val">{{ $profile->contact_no ?? '—' }}</td>
                        </tr>
                    </table>
                </div>

            </div>
        </div>

        <!-- ── Ledger Table ────────────────────────────── -->
        <?php
        $disbursements = $profile->disbursements->whereNull('deleted_at');

        // Group by year_level preserving sort order; keep REVIEW separate so it always renders last
        $grouped = [];
        $reviewRows = [];
        foreach ($disbursements as $d) {
            if (strtoupper($d->year_level ?? '') === 'REVIEW') {
                $reviewRows[] = $d;
            } else {
                $grouped[$d->year_level ?? '—'][] = $d;
            }
        }

        $grandTotal = $disbursements->sum('amount');

        // Total ROS from unique terms
        $uniqueTermsForRos = $disbursements->unique(fn($d) => ($d->year_level ?? '') . '|' . ($d->semester ?? '') . '|' . ($d->academic_year ?? ''));
        $totalRosMonths = 0;
        foreach ($uniqueTermsForRos as $d) {
            $isTrimester = str_contains(strtolower($d->semester ?? ''), 'trimester');
            $totalRosMonths += $isTrimester ? 4 : 6;
        }
        $totalRosVal = $totalRosMonths / 12;
        if ($totalRosVal <= 0) {
            $totalRosYrs = '—';
        } elseif ($totalRosVal == floor($totalRosVal)) {
            $totalRosYrs = intval($totalRosVal) . ' YRS';
        } else {
            $totalRosYrs = number_format($totalRosVal, 1) . ' YRS';
        }
        ?>

        <table class="ledger-table">
            <thead>
                <tr>
                    <th>SCHOLARSHIP COVERAGE<br>(YEAR LEVEL)</th>
                    <th>ACADEMIC YEAR</th>
                    <th>SEMESTER</th>
                    <th>DATE OF OBLIGATION REQUEST</th>
                    <th>OBLIGATION REQUEST NO.</th>
                    <th>TYPE OF PAYMENT</th>
                    <th>AMOUNT</th>
                    <th>EQUIVALENT NO. OF YEARS FOR ROS</th>
                </tr>
            </thead>
            <tbody>
                @if($grouped)
                @foreach($grouped as $yearLevel => $rows)
                @foreach($rows as $i => $d)
                <?php
                $isTrimester = str_contains(strtolower($d->semester ?? ''), 'trimester');
                $rosYrs = $isTrimester ? '4 MONTHS' : '6 MONTHS';
                ?>
                <tr>
                    @if($i === 0)
                    <td class="coverage-cell" rowspan="{{ count($rows) }}">{{ strtoupper($yearLevel) }}</td>
                    @endif
                    <td>{{ $d->academic_year ?? '—' }}</td>
                    <td>{{ $d->semester ?? '—' }}</td>
                    <td>{{ $d->date_obligated ? \Carbon\Carbon::parse($d->date_obligated)->format('m/d/Y') : '—' }}</td>
                    <td>{{ $d->obr_no ?? '—' }}</td>
                    <td>{{ $d->disbursement_type ? strtoupper($d->disbursement_type) : '—' }}</td>
                    <td class="amount-cell">{{ $d->amount !== null ? number_format($d->amount, 2) : '—' }}</td>
                    <td class="amount-cell">{{ $rosYrs }}</td>
                </tr>
                @endforeach
                @endforeach
                @else
                <tr>
                    <td colspan="8" style="text-align:center;padding:12px;color:#333;">No records found.</td>
                </tr>
                @endif
                @if(count($reviewRows))
                @foreach($reviewRows as $i => $d)
                <?php
                $isTrimester = str_contains(strtolower($d->semester ?? ''), 'trimester');
                $rosYrs = $isTrimester ? '4 MONTHS' : '6 MONTHS';
                $isLastReview = ($i === count($reviewRows) - 1);
                ?>
                <tr class="{{ $isLastReview ? 'pre-total-row' : '' }}">
                    @if($i === 0)
                    <td class="coverage-cell" rowspan="{{ count($reviewRows) }}">REVIEW</td>
                    @endif
                    <td>{{ $d->academic_year ?? '—' }}</td>
                    <td>{{ $d->semester ?? '—' }}</td>
                    <td>{{ $d->date_obligated ? \Carbon\Carbon::parse($d->date_obligated)->format('m/d/Y') : '—' }}</td>
                    <td>{{ $d->obr_no ?? '—' }}</td>
                    <td>{{ $d->disbursement_type ? strtoupper($d->disbursement_type) : '—' }}</td>
                    <td class="amount-cell">{{ $d->amount !== null ? number_format($d->amount, 2) : '—' }}</td>
                    <td class="amount-cell">{{ $rosYrs }}</td>
                </tr>
                @endforeach
                @else
                <tr class="pre-total-row">
                    <td class="coverage-cell">REVIEW</td>
                    <td colspan="7" style="text-align:center;">N/A</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td colspan="6" style="text-align:right;">TOTAL</td>
                    <td class="amount-cell">{{ number_format($grandTotal, 2) }}</td>
                    <td class="amount-cell">{{ $totalRosYrs }}</td>
                </tr>
            </tbody>
        </table>

        <!-- ── Signatories ─────────────────────────── -->
        <div class="signatories">
            <div class="signatory-block">
                <p class="signatory-label">Prepared by:</p>
                <p class="signatory-name">{{ strtoupper($preparedBy->name ?? '—') }}</p>
                <p class="signatory-designation">{{ strtoupper($preparedBy->office_designation ?? '—') }}</p>
            </div>
            <div class="signatory-block" style="margin-left:auto;">
                <p class="signatory-label">Approved by:</p>
                <p class="signatory-name">NUR-AINA S. IBRAHIM</p>
                <p class="signatory-designation">Program Manager</p>
            </div>
        </div>
        <div class="signatories" style="margin-top: 48px;">
            <div class="signatory-block" style="margin-top: 28px !important;">
                <p class="signatory-label">Conformed by:</p>
                <p class="signatory-name" style="text-decoration: overline;">Signature over printed name</p>
                <p class="signatory-designation">(PGP SCHOLAR)</p>
            </div>
        </div>

        <!-- ── Footer ─────────────────────────────── -->
        <div style="margin-top: 24px; text-align: right; font-size: 9px; color: #999;">
            Date Generated: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
        </div>

    </div>
</body>

</html>