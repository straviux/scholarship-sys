<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Scholars</title>
    @include('vouchers.styles')
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm;
        }

        /* Header */
        .header {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 0;
        }

        .header-logo {
            position: absolute;
            left: 5%;
            width: 90px;
            height: 90px;
        }

        .header-logo img {
            width: 100%;
            height: auto;
        }

        .header-logo-right {
            position: absolute;
            right: 5%;
            width: 90px;
            height: 90px;
        }

        .header-logo-right img {
            width: 100%;
            height: auto;
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

    // Load and encode Yakap logo as base64 for PDF embedding
    $yakapLogoPath = public_path('images/yakap-logo.svg');
    if (file_exists($yakapLogoPath)) {
        $yakapLogoImage = file_get_contents($yakapLogoPath);
        $yakapLogoBase64 = base64_encode($yakapLogoImage);
        $yakapLogoDataUrl = 'data:image/svg+xml;base64,' . $yakapLogoBase64;
    } else {
        $yakapLogoDataUrl = null;
    }
    ?>
    <div class="container">
        <!-- Content Area -->
        <div style="display: flex; flex-direction: column; flex: 1;">
            <!-- Header -->
            <div class="header border-none">
                @if($logoDataUrl)
                <div class="header-logo">
                    <img src="{{ $logoDataUrl }}" alt="PGP Logo">
                </div>
                @endif
                @if($yakapLogoDataUrl)
                <div class="header-logo-right">
                    <img src="{{ $yakapLogoDataUrl }}" alt="Yakap Logo">
                </div>
                @endif
                <div class="header-text">
                    <p class="text-lg text-center leading-tight">Republic of the Philippines</p>
                    <p class="text-md text-center leading-tight">Provincial Government of Palawan</p>
                    <p class="text-md text-center leading-tight">OFFICE OF THE GOVERNOR</p>
                    <p class="text-md text-center font-semibold leading-tight">AKBAY SA MAG-AARAL YAMAN NG KINABUKASAN</p>
                    <p class="text-md text-center leading-tight">(PROGRAMANG PANG-EDUKASYON PARA SA PALAWEÑO)</p>
                </div>
            </div>

            <!-- Content Area for your additions -->
            <div style="flex: 1; display: flex; flex-direction: column; margin-top: 20px;">
                @php
                // Get school info from first scholar
                $schoolName = '';
                $courseName = '';
                $term = '';
                $academicYear = '';

                if($voucher->scholar_ids && count($voucher->scholar_ids) > 0) {
                $firstScholar = $voucher->scholar_ids[0];
                $profileId = is_array($firstScholar) ? $firstScholar['profile_id'] : $firstScholar;
                $recordId = is_array($firstScholar) ? ($firstScholar['scholarship_record_id'] ?? null) : null;

                $record = null;

                // First try: Use the provided record ID
                if($recordId) {
                $record = \App\Models\ScholarshipRecord::with('course', 'school')
                ->find($recordId);
                }

                // Second try: Get latest active/non-soft-deleted record for this profile
                if(!$record && $profileId) {
                $record = \App\Models\ScholarshipRecord::where('profile_id', $profileId)
                ->with('course', 'school')
                ->whereNull('deleted_at') // Not soft deleted
                ->orderBy('created_at', 'desc')
                ->first();
                }

                if($record) {
                // Try school_name field first
                $schoolName = $record->school_name ?? '';

                // If school_name is empty, try to get from school relationship
                if(empty($schoolName) && $record->school) {
                $schoolName = $record->school->name ?? '';
                }

                $term = $record->term ?? '';
                $academicYear = $record->academic_year ?? '';

                // Get course name from relationship if available
                if($record->course) {
                $courseName = $record->course->name ?? '';
                }
                }
                }


                @endphp

                <!-- School Information Section -->
                <div style="margin-bottom: 20px;">

                    @if($schoolName)
                    <div class="row border-none">
                        <div class="col col-grow col-center col-vcenter">
                            <span class="text-lg font-semibold tracking-wide">{{ $schoolName}}</span>
                        </div>
                    </div>
                    @endif

                    <div class="row border-none">
                        <div class="col col-grow col-center col-vcenter">
                            <span class="text-lg font-semibold tracking-wide">LIST OF SCHOLARS</span>
                        </div>
                    </div>




                    <div class="row border-none">
                        <div class="col col-grow col-center col-vcenter">
                            <span class="text-lg font-semibold tracking-wide">{{ $term .' '. $academicYear }}</span>
                        </div>
                    </div>

                    <div class="row border-none" style="margin-top:32px">
                        <div class="col col-grow col-center col-vcenter">
                            <span class="text-lg font-semibold tracking-wide">{{ $courseName }}</span>
                        </div>
                    </div>


                </div>


                <table>
                    <thead>
                        <tr>
                            <th class="text-lg">No.</th>
                            <th class="text-lg">Name of Scholar/s</th>
                            <th class="text-lg">Year</th>
                            <th class="text-lg">Amount</th>
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
                        ->orderBy('created_at', 'desc')
                        ->first();
                        }

                        if($record) {
                        $year = $record->year_level ?? '';
                        }

                        if($profile) {
                        $scholars[] = [
                        'name' => $profile->first_name . ' ' . $profile->last_name,
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
                        <tr>
                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                            <td style="padding: 8px;" class="text-lg font-semibold">{{ $scholar['name'] }}</td>
                            <td style="text-align: center;" class="text-lg font-semibold">{{ $scholar['year'] ?? 'N/A' }}</td>
                            <td style="text-align: right; padding: 8px;" class="text-lg font-semibold">₱ {{ number_format($scholar['amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">No scholars in this list</td>
                        </tr>
                        @endforelse

                        @php
                        $scholarCount = count($scholars);
                        $rowsNeeded = max(2 - $scholarCount, $scholarCount > 0 ? 0 : 1);
                        @endphp
                        @for($i = 0; $i < $rowsNeeded; $i++)
                            <tr>
                            <td style="text-align: center;">&nbsp;</td>
                            <td style="padding: 8px;">&nbsp;</td>
                            <td style="text-align: center;">&nbsp;</td>
                            <td style="text-align: right; padding: 8px;">&nbsp;</td>
                            </tr>
                            @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td style="text-align: right; padding: 8px;" class="text-xl font-semibold">TOTAL</td>
                            <td style="text-align: right; padding: 8px;" class="text-xl font-semibold">₱ {{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row">
                <div class="col col-grow col-center" style="margin-top: 150px;">
                    <div class="text-center">
                        <p class="text-lg font-semibold tracking-wide underline">NUR-AINA S. IBRAHIM</p>
                        <p class="text-lg" style="margin-top:-2px !important">Program Manager</p>
                        <p class="text-lg" style="margin-top:-4px !important">Akbay sa Mag-Aaral Yaman ng Kinabukasan</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</body>

</html>