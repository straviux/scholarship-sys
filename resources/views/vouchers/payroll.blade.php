<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - {{ $voucher->voucher_number }}</title>
    @include('vouchers.styles')
    <style>
        @page {
            size: 13in 8.5in;
            margin: 6mm 6mm;
        }


        /* Header */
        .header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px 0;
        }

        .header-logo {
            position: absolute;
            left: 27%;
            width: 80px;
            height: 80px;
        }

        .header-logo img {
            width: 100%;
            height: auto;
        }

        /* .payroll-table {
            width: 100%;
            flex: 1;
        } */
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
    <div class="container ">
        <!-- Content Area (flexible) -->
        <div style="display: flex; flex-direction: column; flex: 1;">
            <!-- Header -->
            <div class="header border-none">
                @if($logoDataUrl)
                <div class="header-logo">
                    <img src="{{ $logoDataUrl }}" alt="PGP Logo">
                </div>
                @endif
                <div class="header-text">
                    <p class="text-lg font-semibold text-center leading-tight">GENERAL PAYROLL</p>
                    <p class="text-md text-center leading-tight">PROVINCIAL GOVERNMENT OF PALAWAN</p>
                    <p class="text-md font-semibold text-center leading-tight">AKBAY SA MAG-AARAL YAMAN NG KINABUKASAN</p>
                    <p class="text-md text-center leading-tight">(PROGRAMANG PANG-EDUKASYON PARA SA PALAWEÑO)</p>
                </div>
            </div>

            <!-- Payroll Title Row -->
            <div class="row border-none">
                <div class="col col-grow col-center col-vcenter">
                    <span class="text-sm" style="font-size: 10pt;">{!! $voucher->explanation !!}</span>
                </div>
            </div>

            @php
            // Get term and academic year from voucher, fallback to first scholar's record if null
            $term = $voucher->semester ?? null;
            $academicYear = $voucher->academic_year ?? null;

            // If not available in voucher, fetch from first scholar's record
            if(!$term || !$academicYear) {
            if($voucher->scholar_ids && count($voucher->scholar_ids) > 0) {
            $firstScholar = $voucher->scholar_ids[0];
            $profileId = is_array($firstScholar) ? $firstScholar['profile_id'] : $firstScholar;
            $recordId = is_array($firstScholar) ? ($firstScholar['scholarship_record_id'] ?? null) : null;

            $record = null;

            // First try: Use the provided record ID
            if($recordId) {
            $record = \App\Models\ScholarshipRecord::find($recordId);
            }

            // Second try: Get latest active/non-soft-deleted record for this profile
            if(!$record && $profileId) {
            $record = \App\Models\ScholarshipRecord::where('profile_id', $profileId)
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->first();
            }

            if($record) {
            if(!$term) $term = $record->term ?? '';
            if(!$academicYear) $academicYear = $record->academic_year ?? '';
            }
            }
            }
            @endphp

            <!-- Payroll Title Row -->
            <div class="row border-none pt-2">
                <div class="col col-grow col-left col-vcenter">
                    <span class="font-bold" style="font-size:10pt">FOR ACADEMIC YEAR {{ explode(' ', $term)[0] ?? $term }} SEM {{ $academicYear }} </span>
                </div>
            </div>

            <!-- Payroll Table Header -->
            <div style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                <table class="text-base" style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr class="row">
                            <th class="col col-60 col-center col-vcenter bg-green-dark" style="border: 1px solid #333;font-size:11pt">
                                <span>No.</span>
                            </th>
                            <th class="col col-360  col-center col-vcenter bg-green-dark" style="border: 1px solid #333;font-size:11pt">
                                <span>Names</span>
                            </th>
                            <th class="col col-200  col-center col-vcenter bg-green-dark" style="border: 1px solid #333;font-size:11pt  ">
                                <span>Year Level</span>
                            </th>
                            <th class="col col-200  col-center col-vcenter bg-green-dark" style="border: 1px solid #333;font-size:11pt">
                                <span>Amount</span>
                            </th>

                            <th class="col col-grow  col-center col-vcenter bg-green-dark" style="border: 1px solid #333;font-size:11pt">
                                <span>Signature</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $counter = 1;
                        $totalAmount = 0;
                        $scholars = [];

                        // Fetch scholar details for each profile_id
                        if($voucher->scholar_ids && count($voucher->scholar_ids) > 0) {
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
                        ->orderBy('updated_at', 'desc')
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
                        }
                        @endphp

                        @forelse($scholars as $scholar)
                        @php
                        $totalAmount += $scholar['amount'];
                        @endphp
                        <tr class="row">
                            <td class="col col-60 col-center col-vcenter text-base font-bold" style="border: 1px solid #333;">
                                {{ $loop->iteration }}
                            </td>
                            <td class="col col-360 col-left col-vcenter font-bold" style="border: 1px solid #333;font-size:11pt">
                                {{ $scholar['name'] }}
                            </td>
                            <td class="col col-200 col-center col-vcenter font-bold" style="border: 1px solid #333;font-size:11pt">
                                {{ $scholar['year'] ?? 'N/A' }}
                            </td>
                            <td class="col col-200 col-right col-vcenter font-bold" style="border: 1px solid #333;font-size:11pt">
                                ₱ {{ number_format($scholar['amount'], 2) }}
                            </td>
                            <td class="col col-grow col-center col-vcenter" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                        </tr>
                        @empty
                        <tr class="row">
                            <td colspan="5" class="col col-12 col-center col-vcenter" style="padding: 10px 0; border: 1px solid #333;">
                                No scholars included in this payroll
                            </td>
                        </tr>
                        @endforelse

                        <!-- Minimum 5 rows filler -->
                        @php
                        $scholarCount = count($scholars);
                        $rowsNeeded = max(0, 5 - $scholarCount);
                        @endphp
                        @for($i = 0; $i < $rowsNeeded; $i++)
                            <tr class="row ">
                            <td class="col col-60 col-center col-vcenter text-base" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                            <td class="col col-360 col-left col-vcenter text-base" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                            <td class="col col-200 col-center col-vcenter text-base" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                            <td class="col col-200 col-right col-vcenter text-base" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                            <td class="col col-grow col-center col-vcenter text-base" style="border: 1px solid #333;">
                                &nbsp;
                            </td>
                            </tr>
                            @endfor
                    </tbody>
                    <tfoot>
                        <tr class="row ">
                            <td class="col col-60 col-center col-vcenter text-base" style="border: 1px solid #333;">
                            </td>
                            <td class="col col-360 col-center col-vcenter text-base" style="border: 1px solid #333;">
                            </td>
                            <td class="col col-200 col-center col-vcenter" style="border: 1px solid #333;font-size:11pt">
                                <span class="font-bold">GRAND TOTAL</span>
                            </td>
                            <td class="col col-200 col-right col-vcenter text-base font-bold" style="border: 1px solid #333;font-size:11pt">
                                ₱ {{ number_format($totalAmount ?? $voucher->amount ?? 0, 2) }}
                            </td>
                            <td class="col col-grow col-center col-vcenter text-base" style="border: 1px solid #333;">
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <div class="row pt-4">
                    <div class="col-400">CERTIFIED CORRECT:</div>
                    <div class="col-360" style="margin-left:26px">CERTIFIED cash available</div>
                    <div class="col-auto" style="margin-left:38px">
                        <p>CERTIFIED: Each scholars whose name appears above <br> has been paid the amount indicated opposite his/her name</p>
                    </div>
                </div>

                <div class="row" style="padding-top:44px">
                    <div class="col-400">
                        <div style="width:50%;" class="text-center">
                            <p class="font-bold">NUR-AINA S. IBRAHIM</p>
                            <p class="border-top text-sm" style="margin-top: 2px;">Program Manager</p>
                        </div>
                    </div>
                    <div class="col-360">
                        <div style="width:50%;margin-left:26px" class="text-center">
                            <p class="font-bold">ELINO P. MONDRAGON</p>
                            <p class="border-top text-sm" style="margin-top: 2px;">Provincial Treasurer</p>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div style="width:240px;margin-left:68px" class="text-center">
                            <p>&nbsp;</p>
                            <p class="border-top text-sm" style="margin-top: 2px;">Disbursing Officer</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Fixed Section - Signatures -->
            <div style="display: flex; flex-direction: column; flex: 0 0 auto; padding-top: 28px;">


                <!-- Names and Positions -->
                <div class="row pt-4">
                    <div class="col-400 text-sm">CERTIFIED CORRECT: as to completeness and proprierty of supporting documents.</div>
                    <div class="col-360 text-sm" style="margin-left:26px">Approved for payment.</div>
                    <div class="col-auto">

                    </div>
                </div>
            </div>
            <div class="row" style="padding-top:44px">
                <div class="col-400">
                    <div style="width:50%;" class="text-center">
                        <p class="font-bold">ERLINDA T. RIZADA</p>
                        <p class="border-top  text-sm" style="margin-top: 2px;">Provincial Accountant</p>
                        <p style="margin-top: 2px;" class=" text-sm">Provincial Accountant's Office</p>
                    </div>
                </div>
                <div class="col-360">
                    <div style="width:50%;margin-left:26px" class="text-center">
                        <p class="font-bold">AMY ROA ALVAREZ</p>
                        <p class="border-top text-sm" style="margin-top: 2px;">Governor</p>
                    </div>
                </div>
                <div class="col-auto">
                    <div style="width:240px;margin-left:68px" class="text-center">
                        <p>&nbsp;</p>
                        <p class="border-top text-sm" style="margin-top: 2px;">Date</p>
                    </div>
                </div>
            </div>
        </div>


    </div>

</body>

</html>