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

    public function __construct($profiles, $summary, $filters, $reportType)
    {
        $this->profiles = $profiles;
        $this->filters = $filters;
        $this->summary = $summary;
        $this->reportType = $reportType;
    }

    public function view(): View
    {
        return view('exports.waiting_list', [
            'profiles' => $this->profiles,
            'summary' => $this->summary,
            'reportType' => $this->reportType,
            'filters' => $this->filters,
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

                // Example: Style the header row with a bold font and a background color
                $event->sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'E0E0E0'],
                    ],
                ]);
            },
        ];
    }
}
