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

class ScholarshipReportExport implements FromView, ShouldAutoSize, WithEvents, WithDrawings
{
    protected $profiles;
    protected $filters;
    protected $summary;
    protected $reportType;
    protected $canViewJpm;
    protected $includeRemarks;
    protected $includeGrantProvision;

    public function __construct($profiles, $summary, $filters, $reportType, $canViewJpm = false, $includeRemarks = false, $includeGrantProvision = true)
    {
        $this->profiles = $profiles;
        $this->filters = $filters;
        $this->summary = $summary;
        $this->reportType = $reportType;
        $this->canViewJpm = $canViewJpm;
        $this->includeRemarks = $includeRemarks;
        $this->includeGrantProvision = $includeGrantProvision;
    }

    public function view(): View
    {
        return view('exports.scholarship_report_excel', [
            'profiles' => $this->profiles,
            'summary' => $this->summary,
            'reportType' => $this->reportType,
            'filters' => $this->filters,
            'canViewJpm' => $this->canViewJpm,
            'includeRemarks' => $this->includeRemarks,
            'includeGrantProvision' => $this->includeGrantProvision,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Style the entire data range
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

                // Apply JPM highlighting for list reports (only if user has permission)
                if ($this->reportType === 'list' && $this->canViewJpm) {
                    $dataStartRow = 8;

                    // Sort profiles - approved by year level, others by date filed
                    $sortedProfiles = $this->profiles->sortBy(function ($profile) {
                        $grant = optional($profile->scholarshipGrant->first());
                        $unifiedStatus = $grant->unified_status ?? '';

                        // For approved/active status, sort alphabetically by year level
                        if (in_array($unifiedStatus, ['approved', 'active'])) {
                            $yearLevel = $grant->year_level ?? 'ZZZZ';
                            return [$yearLevel, $profile->last_name, $profile->first_name];
                        }

                        // For other statuses, sort by date filed
                        $dateFiled = $grant->date_filed ?? '9999-12-31';
                        $createdAt = $profile->created_at ?? '9999-12-31 23:59:59';
                        return [$dateFiled, $createdAt];
                    });

                    $currentRow = $dataStartRow;
                    foreach ($sortedProfiles as $profile) {
                        $isJpm = $profile->is_jpm_member || $profile->is_father_jpm || $profile->is_mother_jpm || $profile->is_guardian_jpm;

                        if ($isJpm) {
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
        $pgpLogo = new Drawing();
        $pgpLogo->setName('PGP Logo');
        $pgpLogo->setDescription('Provincial Government of Palawan Logo');
        $pgpLogo->setPath(public_path('images/pgp-logo.png'));
        $pgpLogo->setHeight(60);
        $pgpLogo->setCoordinates('A1');

        $yakapLogo = new Drawing();
        $yakapLogo->setName('Yakap Logo');
        $yakapLogo->setDescription('Yakap Logo');
        $yakapLogo->setPath(public_path('images/yakap-logo.png'));
        $yakapLogo->setHeight(60);
        $yakapLogo->setCoordinates('J1');

        return [$pgpLogo, $yakapLogo];
    }
}
