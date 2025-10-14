<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class WaitingListExport implements FromView, ShouldAutoSize, WithEvents, WithDrawings
{
    protected $profiles;
    protected $filters;
    protected $summary;
    protected $reportType;
    protected $canViewJpm;

    public function __construct($profiles, $summary, $filters, $reportType, $canViewJpm = false)
    {
        $this->profiles = $profiles;
        $this->filters = $filters;
        $this->summary = $summary;
        $this->reportType = $reportType;
        $this->canViewJpm = $canViewJpm;
    }

    public function view(): View
    {
        return view('exports.waiting_list', [
            'profiles' => $this->profiles,
            'summary' => $this->summary,
            'reportType' => $this->reportType,
            'filters' => $this->filters,
            'canViewJpm' => $this->canViewJpm,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Style the entire data range (A1 to the last cell)
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();
                $cellRange = 'A7:' . $highestColumn . $highestRow;

                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // Style the header row with a bold font and a background color
                // $headerRow = $this->reportType === 'summary' ? 1 : 2;
                // $event->sheet->getStyle('A' . $headerRow . ':' . $highestColumn . $headerRow)->applyFromArray([
                //     'font' => [
                //         'bold' => true,
                //     ],
                //     'fill' => [
                //         'fillType' => Fill::FILL_SOLID,
                //         'color' => ['rgb' => 'E0E0E0'],
                //     ],
                // ]);

                // Apply JPM highlighting for list reports (only if user has permission)
                if ($this->reportType === 'list' && $this->canViewJpm) {
                    // Data starts at row 8:
                    // Rows 1-4: Organization header table (Republic, Provincial Govt, Akbay, Programang)
                    // Row 5: "Waiting List" title row
                    // Row 6: Column headers (#, Seq, Name, etc.)
                    // Row 7: (empty/spacing)
                    // Row 8+: Actual data rows
                    $dataStartRow = 8;
                    $sortedProfiles = $this->profiles->sortBy(function ($profile) {
                        $dateFiled = optional($profile->scholarshipGrant->first())->date_filed;
                        return [$dateFiled, $profile->created_at];
                    });

                    $currentRow = $dataStartRow;
                    foreach ($sortedProfiles as $profile) {
                        // Check if applicant, parent, or guardian is JPM
                        $isJpm = $profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm;

                        if ($isJpm) {
                            // Green highlight for any JPM affiliation
                            $event->sheet->getStyle('A' . $currentRow . ':' . $highestColumn . $currentRow)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'color' => ['rgb' => 'D1FAE5'], // Green
                                ],
                            ]);
                        }

                        $currentRow++;
                    }
                }
            },
        ];
    }

    public function drawings()
    {


        $drawing = new Drawing();
        $drawing->setName('PGL Logo');
        $drawing->setDescription('PGP Logo');
        $drawing->setPath(public_path('images/pgp-logo.png')); // Path to your logo file
        $drawing->setHeight(80); // Set the height of the logo
        $drawing->setCoordinates('A1'); // Positioning the logo at cell A1

        $drawing2 = new Drawing();
        $drawing2->setName('Yakap Logo');
        $drawing2->setDescription('Yakap Logo');
        $drawing2->setPath(public_path('images/yakap-logo.png'));
        $drawing2->setHeight(80);
        $drawing2->setCoordinates('K1'); // Position at last column, row 1

        return [$drawing, $drawing2];
    }
}
