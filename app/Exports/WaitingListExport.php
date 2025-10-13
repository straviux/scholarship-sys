<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class WaitingListExport implements FromView, ShouldAutoSize, WithEvents
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
                $cellRange = 'A1:' . $highestColumn . $highestRow;

                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // Style the header row with a bold font and a background color
                $headerRow = $this->reportType === 'summary' ? 1 : 2;
                $event->sheet->getStyle('A' . $headerRow . ':' . $highestColumn . $headerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'E0E0E0'],
                    ],
                ]);

                // Apply JPM highlighting for list reports (only if user has permission)
                if ($this->reportType === 'list' && $this->canViewJpm) {
                    $dataStartRow = 3; // Data starts from row 3 (after title and header)
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
}
