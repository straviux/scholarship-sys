<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursement Voucher- {{ $voucher->voucher_number }}</title>
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
            border: 1px solid #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        @page {
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
            min-width: 160px;
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
            min-height: 100%;
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
            min-width: 160px;
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

        /* Make blank rows stretch */
        .obr-info-row.no-border-bottom {
            flex-shrink: 0;
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
        <!-- Content Area (flexible) -->
        <div style="display: flex; flex-direction: column; flex: 1;">
            <!-- Header -->
            <div class="header">
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
            <div class="obr-info-row">
                <div class="label">DISBURSEMENT VOUCHER</div>
                <div class="number">No.</div>
            </div>

            <!-- OBR Payee Row -->
            <div class="obr-info-row">
                <div class="column_1">Mode of Payment:</div>
                <div class="column_2" style="flex-direction:row;gap:10px;padding:3px 0;">
                    <div style="display:flex;align-items:center;">
                        <div style="width: 20px;height: 20px;border: 1px solid #333;margin:0 10px"></div>Check
                    </div>
                    <div style="display:flex;align-items:center;">
                        <div style="width: 20px;height: 20px;border: 1px solid #333;margin:0 10px"></div>Cash
                    </div>
                    <div style="display:flex;align-items:center;">
                        <div style="width: 20px;height: 20px;border: 1px solid #333;margin:0 10px"></div>Others
                    </div>
                </div>
                <!-- <div class="column_5">&nbsp;</div> -->
            </div>

            <!-- OBR Office Row -->
            <div class="obr-info-row">
                <div class="column_1">Payee:</div>
                <div style="flex: 0.6; padding-left: 2px; text-align: left; display: flex; align-items: center; border-right: 1px solid #333;font-weight:600">{{ $voucher->payee_name ?? '_______________' }}</div>
                <div style="flex: 0.4; text-align: left; display: flex; flex-direction: column; ">
                    <div style="flex: 0.5; display: flex; align-items: center; border-bottom: 1px solid #333;border-right: 1px solid #333;padding-bottom:13px;text-indent:2px"><small>TIN/Employee No.</small></div>
                    <div style="flex: 0.5; display: flex; align-items: center;padding-left:2px"><small>Responsibility Center</small></div>
                </div>
                <div class="column_5" style="display: flex; flex-direction: column;padding-left:0; ">
                    <div style="flex: 0.5; display: flex; align-items: center; border-bottom: 1px solid #333;padding-bottom:16px;text-indent:2px"><small>Obligation Request No.</small></div>
                    <div style="flex: 0.5; display: flex; align-items: center;">&nbsp;</div>

                </div>
            </div>

            <!-- OBR Address Row -->
            <div class="obr-info-row">
                <div class="column_1">Address:</div>
                <!-- <div class="column_2" style="font-weight:600">{{ $voucher->payee_address ?? '_______________' }}</div> -->
                <div style="flex: 0.6; padding-left: 2px; text-align: left; display: flex; align-items: center; border-right: 1px solid #333;font-weight:600">{{ $voucher->payee_address ?? '_______________' }}</div>
                <div style="flex: 0.4; text-align: left; align-items: center; border-right: 1px solid #333;">
                    <div style="padding-left:2px">
                        <small>Office/Unit/Project:Education for</small>
                        <small>Development Program</small>
                    </div>
                </div>
                <div class="column_5"><small>Code</small></div>
            </div>

            <!-- OBR Address Row Continued -->
            <div class="obr-info-row">
                <div class="column_1">&nbsp;</div>
                <div class="column_2">&nbsp;</div>
                <div class="column_5">&nbsp;</div>
            </div>

            <!-- OBR RCenter Row -->
            <div class="obr-info-row">
                <div class="column_2 center" style="font-style: 11px;">EXPLANATION
                </div>
                <div class="column_5 center" style="font-style: 11px;">
                    AMOUNT
                </div>
            </div>

            <!-- DV BLANK ROW -->
            <div class="obr-info-row no-border-bottom ">
                <div class="label">&nbsp;</div>
                <div class="number">&nbsp;</div>
            </div>

            <!-- EXPLANATION VALUE ROW -->
            <div class="obr-info-row no-border-bottom">
                <div class="column_2 center" style="font-size:12px;line-height:1.2">{!! $voucher->explanation ?? '_______________' !!}</div>
                <div class="column_5" style="justify-content:flex-end!important;padding-right:10px;">₱ {{ $voucher->amount ?? '_______________' }}</div>
            </div>
            <!-- Scholar's name ROW -->
            <div class="obr-info-row no-border-bottom">
                <div class="column_2 center" style=font-weight:600;">
                    <br>
                    @php
                    $scholarIds = $voucher->scholar_ids ?? [];
                    if (is_array($scholarIds) && count($scholarIds) > 0) {
                    // Extract profile_ids from the array of objects/arrays
                    $profileIds = array_map(function($scholar) {
                    return is_array($scholar) ? $scholar['profile_id'] : $scholar->profile_id;
                    }, $scholarIds);

                    $scholars = \App\Models\ScholarshipProfile::whereIn('profile_id', $profileIds)->get();
                    $scholarName = $scholars && count($scholars) > 0
                    ? ($scholars[0]->first_name ?? '') . ' ' . ($scholars[0]->last_name ?? '')
                    : '_______________';
                    } else {
                    $scholarName = '_______________';
                    }
                    @endphp
                    {{ "(".$scholarName.")" }}
                </div>
                <div class="column_5">&nbsp;</div>
            </div>

            <!-- Blank Rows for Space (Auto-computed) -->
            @php
            // Estimate row heights in pixels
            $headerHeight = 80;
            $payeeRowHeight = 60;
            $addressRowHeight = 60;
            $explanationRowHeight = 100; // Increased to account for multi-line explanations
            $scholarRowHeight = 30; // Slightly increased
            $totalRowHeight = 40;
            $certifiedRowHeight = 100;
            $sigRowHeight = 30;
            $printedNameRowHeight = 40;
            $positionRowHeight = 50;
            $approvalRowHeight = 30;
            $checkRowHeight = 50;
            $govRowHeight = 50;
            $agencyRowHeight = 50;

            // Total fixed content height (all sections)
            $fixedHeight = $headerHeight + $payeeRowHeight + $addressRowHeight + 20 + 40 +
            $explanationRowHeight + $scholarRowHeight + $totalRowHeight +
            $certifiedRowHeight + ($sigRowHeight * 3) + ($printedNameRowHeight * 2) +
            $positionRowHeight + $approvalRowHeight + $checkRowHeight + $govRowHeight + $agencyRowHeight;

            // Page height in pixels (216mm x 330mm = ~815 x 1240 pixels at 96dpi)
            $pageHeight = 1240;
            $availableHeight = $pageHeight - $fixedHeight;

            // Each blank row is approximately 40px
            $blankRowHeight = 40;
            // More conservative calculation: subtract 3 instead of 1 to prevent overflow to second page
            $numBlankRows = max(2, floor($availableHeight / $blankRowHeight) - 3);
            @endphp

            @for ($i = 0; $i < $numBlankRows; $i++)
                <div class="obr-info-row no-border-bottom" style="min-height: 40px;">
                <div class="column_2">&nbsp;</div>
                <div class="column_5">&nbsp;</div>
        </div>
        @endfor

        <!-- Final Spacer Row to Fill Remaining Space -->
        <div class="obr-info-row no-border-bottom" style="flex-grow: 1;">
            <div class="column_2">&nbsp;</div>
            <div class="column_5">&nbsp;</div>
        </div>

    </div>
    <!-- Bottom Fixed Section -->
    <div style="display: flex; flex-direction: column; flex: 0 0 auto;">
        <!-- Total Amount Row -->
        <div class="obr-info-row" style="border-top:1px solid #333;">
            <div class="column_2" style="font-weight:600;align-items:flex-end!important;padding-right:10px;">TOTAL</div>
            <div class="column_5" style="font-weight:600;justify-content:flex-end!important;padding-right:10px;">₱ {{ $voucher->amount ?? '_______________' }}</div>
        </div>

        <!-- Certified Row -->
        <div class="obr-info-row">
            <div class="column_2" style="flex: 0.5;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="width: 100%;font-weight: bold; vertical-align: top;display:flex; align-items: center;font-size:14px">
                            <div style="border: 1px solid #333;padding:0 18px;margin-top:-1px;margin-left:-3px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;">A</div> <span>Certified</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 100%;padding-top:5px;padding-left:50px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                            <div style="border: 1px solid #333;height:22px;width:20px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>
                            Allotment obligated for the purpose as indicated above
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 100%;padding-top:5px;padding-bottom:2px;padding-left:50px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                            <div style="border: 1px solid #333;height:22px;width:20px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>
                            Supporting documents completed
                        </td>
                    </tr>
                </table>
            </div>
            <div class="column_5" style="flex: 0.5;border-right: none;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px; text-align: left;">
                    <tr>
                        <td style="width: 100%; margin-top:-1x;margin-left:-3px; font-weight: bold; vertical-align: top;display:flex; align-items: center;font-size:14px">
                            <div style="border: 1px solid #333;padding:0 18px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;">B</div> <span>Certified</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-left:50px;padding-top:4px;font-weight: normal; text-wrap: break-word;font-size:11px;display:flex;">
                            <div style="border: 1px solid #333;height:22px;width:20px;margin-right:4px;justify-content: center; display: flex; align-items: center;justify-content: center;"></div>Funds Available
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 100%;font-weight: normal; text-wrap: break-word;font-size:11px">&nbsp;</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signature Row -->
        <div class="obr-info-row" style="min-height: 30px;">
            <div style="flex: 0.455; text-indent:2px;padding:16px 0; border-right: 1px solid #333;"><small>Signature</small></div>
            <div style="flex: 1; border-right: 1px solid #333;">&nbsp;</div>
            <div style="flex: 0.45; text-indent:2px;padding:16px 0; border-right: 1px solid #333;"><small>Signature</small></div>
            <div style="flex: 1;">&nbsp;</div>
        </div>

        <!-- Printed Name Row -->
        <div class="obr-info-row no-border-bottom" style="min-height: 28px;">
            <div style="flex: 0.45; text-indent:2px;padding: 0; border-right: 1px solid #333;padding-right:1px;border-bottom: 1px solid #333;"><small>Printed Name</small></div>
            <div style="flex: 0.70; border-right: 1px solid #333;text-align:center;padding: 0; font-weight:600;border-bottom: 1px solid #333;">ERLINDA T. RIZADA</div>
            <div style="flex: 0.296; text-indent:2px;padding: 0; border-right: 1px solid #333;border-bottom:0"><small>Date</small></div>
            <div style="flex: 0.45; text-indent:2px;padding: 0; border-right: 1px solid #333;border-bottom: 1px solid #333;"><small>Printed Name</small></div>
            <div style="flex: 1;text-align:center;padding: 0; font-weight:600;border-bottom: 1px solid #333;">ELINO P. MONDRAGON</div>
        </div>
        <!-- Printed Name Row -->
        <div class="obr-info-row" style="min-height:24px">
            <div style="flex: 0.45; text-indent:2px;padding: 0; border-right: 1px solid #333;padding-right:1px"><small>Position</small></div>
            <div style="flex: 0.70; border-right: 1px solid #333;text-align:center;padding: 0;line-height:1;padding-right:1px">
                <small>Provincial Accountant</small>
                <small>Provincial Accountant's Office</small>
            </div>
            <div style="flex: 0.296; text-indent:2px;padding: 0; border-right: 1px solid #333;"></div>
            <div style="flex: 0.45; text-indent:2px;padding: 0; border-right: 1px solid #333;"><small>Position</small></div>
            <div style="flex: 1; text-align:center;padding: 0;line-height:1;">
                <small>Provincial Treasurer</small><br>
                <small>Treasurer/Authorized Representative</small>
            </div>
        </div>

        <!-- Signature Row -->
        <div class="obr-info-row" style="min-height:10px;padding:0">
            <div style="flex: 0.14; border-right: 1px solid #333;padding:0;font-weight:600;font-size:16px;text-align:center"><small>C</small></div>
            <div style="flex: 1; border-right: 1px solid #333;padding:0;text-indent:2px"><small>Approved for payment</small></div>
            <div style="flex: 0.14;border-right: 1px solid #333;padding:0;font-weight:600;font-size:16px;text-align:center"><small>D</small></div>
            <div style="flex: 1;text-indent:2px;padding:0"><small>Approved for payment</small></div>
        </div>
        <!-- Signature Row -->
        <div class="obr-info-row" style="min-height: 30px;">
            <div style="flex: 0.455; text-indent:2px; border-right: 1px solid #333; display: flex; align-items: center;"><small>Signature</small></div>
            <div style="flex: 1; border-right: 1px solid #333;padding-right:1px">&nbsp;</div>
            <div style="flex: 0.45; text-indent:2px;border-right: 1px solid #333;"><small>Check No.</small></div>
            <div style="flex: 1; display: flex; flex-direction: column; ">
                <div style="flex: 0.2; border-bottom: 1px solid #333;text-indent:2px"><small>Bank Name</small></div>
                <div style="min-height: 30px;">&nbsp;</div>
            </div>
        </div>
        <!-- Printed Name Row -->
        <div class="obr-info-row">
            <div style="flex: 0.45; text-indent:2px;padding:4px 0; border-right: 1px solid #333;padding-right:1px"><small>Printed Name</small></div>
            <div style="flex: 0.70; border-right: 1px solid #333;text-align:center;padding:4px 0;text-align:center;line-height:0.8;">
                <p style=" font-weight:600">AMY ROA ALVAREZ</p>
                <p style="font-size:12px">Governor</p style="font-size:12px">
            </div>
            <div style="flex: 0.296; text-indent:2px;padding:4px 0; border-right: 1px solid #333;"><small>Date</small></div>
            <div style="flex: 0.45; text-indent:2px;padding:4px 0; border-right: 1px solid #333;"><small>Signature</small></div>
            <div style="flex: 1;text-align:center;padding:4px 0; font-weight:600"></div>
        </div>

        <div class="obr-info-row no-border-bottom " style="min-height: 30px;">
            <div style="flex: 0.37; text-indent:2px; border-right: 1px solid #333; display: flex; align-items: center;"></div>
            <div style="flex: 1; border-right: 1px solid #333;padding-right:1px; text-indent:2px"><small>Agency Head/Authorized Representative</small></div>
            <!-- <div style="flex: 0.45; text-indent:2px;border-right: 1px solid #333;"><small>Printed Name</small></div> -->
            <div style="flex: 1; display: flex; flex-direction: column; ">
                <div style="flex: 0.2; border-bottom: 1px solid #333;text-indent:2px"><small>Printed Name</small></div>
                <div style="min-height: 50px;" class="obr-info-row no-border-bottom">
                    <div style="flex: 1; text-indent:2px;padding:4px 0; border-right: 1px solid #333;"><small>OR/Other Documents</small></div>
                    <div style="flex: 0.45; text-indent:2px;padding:4px 0;"><small>JEV. No.</small></div>
                </div>
            </div>
        </div>
    </div>
    </div>

</body>

</html>