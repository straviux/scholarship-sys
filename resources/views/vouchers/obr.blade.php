<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obligation Request - {{ $voucher->voucher_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            border-collapse: collapse;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #000;
            background: #fff;
            padding: 0;
        }

        .center {
            text-align: center !important;
        }

        .obr-info-row .center {
            text-align: center !important;
            justify-content: center !important;
        }

        /* Center class specifically for column_2 with vertical flex layout */
        .obr-info-row .column_2.center {
            align-items: center !important;
            justify-content: center !important;
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
            background: #fff;
            padding: 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        @page {
            /* size: 8.5in 13in; */
            margin: 6mm 6mm;
        }

        /* Header */
        .header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #333;
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
            color: #000;
            text-align: center;
            flex: 1;
            font-size: 14px;
        }

        .header-text p {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .header-text p.main {
            font-size: 14px;
            font-weight: 600;
        }

        .header-text p.sub {
            font-size: 14px;
        }

        .header p {
            font-size: 12px;
            color: #000;
        }

        /* Ensure paragraphs display as block elements */
        p {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.6;
            width: 100%;
            word-wrap: break-word;
            white-space: normal;
        }

        /* Quill editor align classes */
        p.ql-align-center {
            text-align: center;
        }

        p.ql-align-left {
            text-align: left;
        }

        p.ql-align-right {
            text-align: right;
        }

        .obr-info-row {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            padding: 0;
            border-bottom: 1px solid #333;
            font-size: 14px;
        }

        .obr-info-row.no-border-bottom {
            border-bottom: none;
        }

        .obr-info-row.border-top {
            border-top: 1px solid #333;
        }

        .obr-info-row .label {
            font-weight: bold;
            text-align: center;
            padding: 8px 2px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #333;
        }

        .obr-info-row .number {
            padding-left: 2px;
            text-align: left;
            min-width: 260px;
            display: flex;
            align-items: center;
        }

        .obr-info-row .column_1 {
            padding-left: 2px;
            text-align: left;
            width: 120px;
            border-right: 1px solid #333;
            display: flex;
            align-items: center;
        }

        .obr-info-row .column_2 {
            padding-left: 2px;
            font-weight: normal;
            text-align: left;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border-right: 1px solid #333;
        }

        .obr-info-row .column_3 {
            padding-left: 2px;
            text-align: left;
            min-width: 80px;
            display: flex;
            align-items: center;
            border-right: 1px solid #333;
        }

        .obr-info-row .column_4 {
            padding-left: 2px;
            text-align: left;
            min-width: 80px;
            display: flex;
            align-items: center;
            border-right: 1px solid #333;
        }

        .obr-info-row .column_5 {
            padding-left: 2px;
            text-align: left;
            min-width: 260px;
            display: flex;
            flex-direction: row;
            align-items: stretch;
        }

        .obr-info-row .column_5 .fpp {
            width: 60px;
            padding: 0 2px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #333;
        }

        .obr-info-row .column_5 .account-code {
            width: 60px;
            padding: 0 2px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #333;
        }

        .obr-info-row .column_5 .amount {
            flex: 1;
            padding: 0 2px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                max-width: 100%;
            }
        }

        .obr-bottom-section {
            display: flex;
            flex-direction: column;
            background: white;
        }

        .obr-bottom-section .obr-info-row {
            border-bottom: 1px solid #333;
        }
    </style>
</head>

<body>
    <?php
    // Load and encode PGP logo as base64 for PDF embedding
    $logoPath = public_path('images/pgp-logo.svg');
    if (file_exists($logoPath)) {
        $logoImage = file_get_contents($logoPath);
        $logoBase64 = base64_encode($logoImage);
        $logoDataUrl = 'data:image/svg+xml;base64,' . $logoBase64;
    } else {
        $logoDataUrl = null;
    }
    ?>
    <div class="container">
        <!-- Header -->
        <div class="header" style="border-top:1px solid #333;border-left:1px solid #333;border-right:1px solid #333;">
            @if($logoDataUrl)
            <div class="header-logo">
                <img src="{{ $logoDataUrl }}" alt="PGP Logo">
            </div>
            @endif
            <div class="header-text">
                <p class="main">Republic of the Philippines</p>
                <p class="main">PROVINCIAL GOVERNMENT OF PALAWAN</p>
                <p class="sub">OFFICE OF THE GOVERNOR</p>
            </div>
        </div>

        <!-- OBR Info Row -->
        <div class="obr-info-row" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="label">OBLIGATION REQUEST</div>
            <div class="number" style="font-size:9pt">No.</div>
        </div>

        <!-- OBR Payee Row -->
        <div class="obr-info-row" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1" style="font-size:9pt">Payee:</div>
            <div class="column_2" style="font-weight:600; font-size:11pt">{{ $voucher->payee_name ?? '_______________' }}</div>
            <div class="column_5">&nbsp;</div>
        </div>

        <!-- OBR Office Row -->
        <div class="obr-info-row" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1" style="font-size:9pt">Office:</div>
            <div class="column_2">&nbsp;</div>
            <div class="column_5">&nbsp;</div>
        </div>

        <!-- OBR Address Row -->
        <div class="obr-info-row" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1" style="font-size:9pt">Address:</div>
            <div class="column_2" style="font-weight:600; font-size:11pt">{{ $voucher->payee_address ?? '_______________' }}</div>
            <div class="column_5">&nbsp;</div>
        </div>

        <!-- OBR RCenter Row -->
        <div class="obr-info-row" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1 center" style="font-size:9pt">Responsibility Center:</div>
            <div class="column_2 center" style="font-weight:600">PARTICULARS</div>
            <div class="column_5">
                <div class="fpp" style="font-size:9pt">F.P.P</div>
                <div class="account-code" style="font-size:9pt">Account Code</div>
                <div class="amount" style="font-size:9pt">Amount</div>
            </div>
        </div>

        <!-- OBR PARTICULARS Row -->
        <div class="obr-info-row no-border-bottom" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1 center">{{ $voucher->responsibility_center ?? '_______________' }}</div>
            <div class="column_2 center" style="font-weight:600">{{ $voucher->particulars_name ?? '_______________' }}</div>
            <div class="column_5">
                <div class="fpp">&nbsp;</div>
                <div class="account-code" style="font-size: 9pt; font-weight: normal;">{{ $voucher->account_code ?? '_______________' }}</div>
                <div class="amount" style="font-size: 11pt; font-weight: normal;">
                    @if($voucher->obr_type === 'REIMBURSEMENT')
                    ₱{{ number_format($voucher->amount, 2) }}
                    @else
                    &nbsp;
                    @endif
                </div>
            </div>
        </div>

        <!-- OBR Particulars Description Row -->
        @if($voucher->particulars_description)
        <div class="obr-info-row no-border-bottom" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1">&nbsp;</div>
            <div class="column_2 center" style="font-size: 11pt;line-height:1.2">
                {!! $voucher->particulars_description !!}
            </div>
            <div class="column_5">
                <div class="fpp">&nbsp;</div>
                <div class="account-code">&nbsp;</div>
                <div class="amount">&nbsp;</div>
            </div>
        </div>
        @endif

        <!-- OBR Name of Scholars Rows (Hidden if REIMBURSEMENT) -->
        @if($voucher->obr_type !== 'REIMBURSEMENT' && $voucher->scholar_ids && count($voucher->scholar_ids) > 0)
        @php
        // Fetch scholar details for each profile_id
        $scholars = [];
        foreach($voucher->scholar_ids as $scholarData) {
        $profileId = is_array($scholarData) ? $scholarData['profile_id'] : $scholarData;
        $recordId = is_array($scholarData) ? ($scholarData['scholarship_record_id'] ?? null) : null;
        if($profileId) {
        $profile = \App\Models\ScholarshipProfile::find($profileId);
        $year = '';
        $record = null;

        // First try: Use the provided record ID
        if($recordId) {
        $record = \App\Models\ScholarshipRecord::find($recordId);
        }

        // Second try: Get latest active/non-soft-deleted record for this profile
        if(!$record) {
        $record = \App\Models\ScholarshipRecord::where('profile_id', $profileId)
        ->whereNull('deleted_at')
        ->orderBy('created_at', 'desc')
        ->first();
        }

        if($record) {
        $year = $record->year_level ?? '';
        }

        if($profile) {
        $scholarName = $profile->last_name . ', ' . $profile->first_name;
        if($profile->middle_name) {
            $scholarName .= ' ' . $profile->middle_name;
        }
        if($profile->ext) {
            $scholarName .= ' ' . $profile->ext;
        }
        $scholars[] = [
        'name' => $scholarName,
        'year' => $year,
        'amount' => $voucher->amount
        ];
        }
        }
        }
        @endphp

        <div class="obr-info-row no-border-bottom" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1">&nbsp;</div>
            <div class="column_2">&nbsp;</div>
            <div class="column_5">
                <div class="fpp">&nbsp;</div>
                <div class="account-code">&nbsp;</div>
                <div class="amount">&nbsp;</div>
            </div>
        </div>
        <div class="obr-info-row no-border-bottom" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1 ">&nbsp;</div>
            <div class="column_2" style="text-decoration: underline;border:none; font-weight:600; font-size:12pt">NAME OF SCHOLARS</div>
            <div class="column_3" style="text-decoration: underline; font-weight:600; font-size:12pt">YEAR</div>
            <div class="column_5">
                <div class="fpp">&nbsp;</div>
                <div class="account-code">&nbsp;</div>
                <div class="amount">&nbsp;</div>
            </div>
        </div>

        @foreach($scholars as $scholar)
        <div class="obr-info-row no-border-bottom" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <div class="column_1">&nbsp;</div>
            <div class="column_2" style="border:none; font-size:11pt">{{ $loop->iteration }}. {{ $scholar['name'] }}</div>
            <div class="column_3" style="font-size:11pt">{{ $scholar['year'] }}</div>
            <div class="column_5">
                <div class="fpp">&nbsp;</div>
                <div class="account-code">&nbsp;</div>
                <div class="amount" style="font-size:11pt">₱ {{ number_format($scholar['amount'], 2) }}</div>
            </div>
        </div>
        @endforeach
        @endif

        <!-- Fill remaining space until bottom section -->
        <div style="flex: 0.8; display: flex; flex-direction: column;">
            <div class="obr-info-row" style="color: transparent; flex: 1; border-bottom: none; border-left:1px solid #333!important;border-right:1px solid #333!important;">
                <div class="column_1">&nbsp;</div>
                <div class="column_2" style="border:none">&nbsp;</div>
                <div class="column_3">&nbsp;</div>
                <div class="column_5">
                    <div class="fpp">&nbsp;</div>
                    <div class="account-code">&nbsp;</div>
                    <div class="amount">&nbsp;</div>
                </div>
            </div>
        </div>

        <!-- OBR Total Row -->
        @if($voucher->scholar_ids && count($voucher->scholar_ids) > 0)
        @endif

        <!-- Bottom Section -->
        <div class="obr-bottom-section" style="border-left:1px solid #333!important;border-right:1px solid #333!important;">
            <!-- TOTAL Row -->
            @if($voucher->scholar_ids && count($voucher->scholar_ids) > 0)
            <div class="obr-info-row no-border-bottom border-top">
                <div class="column_1" style="border: none;"></div>
                <div class="column_2" style="border: none;"></div>
                <div class="column_3" style="font-weight: bold; text-align: center;">TOTAL</div>
                <div class="column_5">
                    <div class="fpp" style="border: none;"></div>
                    <div class="account-code" style="border: none;"></div>
                    <div class="amount" style="font-weight: bold;border: none;font-size:11pt">₱ {{ number_format($voucher->amount * count($voucher->scholar_ids), 2) }}</div>
                </div>
            </div>
            @endif
            <!-- Row 5 -->
            <div class="obr-info-row">
                <div class="column_2" style="flex: 1.08;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr>
                            <td style="width: 100%;font-weight: bold; vertical-align: top;display:flex; align-items: center;font-size:14px">
                                <div style="border: 1px solid #333;padding:0 18px;margin-top:-1px;margin-left:-3px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;">A</div> <span>Certified</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 100%;padding-top:5px;padding-left:50px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                                <div style="border: 1px solid #333;height:22px;width:28px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>
                                Charges to appropriation/allotment necessary, lawful and under my direct supervision
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 100%;padding-top:5px;padding-bottom:2px;padding-left:50px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                                <div style="border: 1px solid #333;height:22px;width:20px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>
                                Supporting documents valid, proper and legal
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="column_3" style="flex: 0.9;border-right: none;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px; text-align: left;">
                        <tr>
                            <td style="width: 100%; margin-top:-25px;margin-left:-3px; font-weight: bold; vertical-align: top;display:flex; align-items: center;font-size:14px">
                                <div style="border: 1px solid #333;padding:0 18px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;">B</div> <span>Certified</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left:50px;padding-top:4px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                                <div style="border: 1px solid #333;height:22px;width:20px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>Existence of Available Appropriation
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 100%;font-weight: normal; text-wrap: break-word;font-size:11px">&nbsp;</td>
                        </tr>

                    </table>
                </div>
            </div>

            <!-- Row 6 -->
            <div class="obr-info-row" style="min-height: 60px;">
                <div class="column_1" style="text-align: center; font-size:9pt; padding-top: 40px;">Signature</div>
                <div class="column_2">&nbsp;</div>
                <div class="column_3" style="text-align: center; font-size:9pt; padding-top: 40px;">Signature</div>
                <div class="column_5">

                </div>
            </div>

            <!-- Row 7 -->
            <div class="obr-info-row">
                <div class="column_1" style="text-align: center; font-size:9pt">Printed Name</div>
                <div class="column_2 center" style="font-weight: 600;font-size:11pt">AMY ROA ALVAREZ</div>
                <div class="column_3" style="text-align: center; font-size:9pt">Printed Name</div>
                <div class="column_5 center" style="font-weight: 600;font-size:11pt">
                    MA. ISABEL E. GUINTO
                </div>
            </div>

            <!-- Row 8 -->
            <div class="obr-info-row" style="min-height: 50px;">
                <div class="column_1" style="text-align: center; font-size:9pt">Position</div>
                <div class="column_2 center" style="font-weight: normal;font-size:11pt">Governor</div>
                <div class="column_3" style="text-align: center; font-size:9pt">Position</div>
                <div class="column_5 center" style="flex-direction: column; justify-content: space-around;font-size:10pt">
                    <div style="padding-bottom: 1px;">Supervising Administrative Officer</div>
                    <div>Acting Provincial Budget Officer</div>
                </div>
            </div>

            <!-- Row 9 -->
            <div class="obr-info-row no-border-bottom">
                <div class="column_1" style="text-align: center;  font-size:9pt">Date</div>
                <div class="column_2">&nbsp;</div>
                <div class="column_3" style="text-align: center;  font-size:9pt">Date</div>
                <div class="column_5">

                </div>
            </div>
        </div>
</body>

</html>