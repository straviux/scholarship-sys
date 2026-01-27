<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VoucherOBRExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $voucher;

    public function __construct($voucher)
    {
        $this->voucher = $voucher;
    }

    /**
     * Get the rows
     */
    public function collection()
    {
        $rows = new Collection();

        // Add title rows
        $rows->push(['PALAWAN STATE UNIVERSITY']);
        $rows->push(['OBLIGATION REQUEST (OBR)']);
        $rows->push([]); // Empty row

        // Add document info
        $rows->push(['OBR No.:', $this->voucher->voucher_number, '', 'Date:', $this->voucher->created_at?->format('M d, Y'), '', 'Type:', ucfirst($this->voucher->voucher_type)]);
        $rows->push([]); // Empty row

        // Add payee information
        $rows->push(['PAYEE INFORMATION']);
        $rows->push(['Name:', $this->voucher->payee_name]);
        $rows->push(['Type:', ucfirst($this->voucher->payee_type)]);
        $rows->push(['Address:', $this->voucher->payee_address ?? '---']);
        $rows->push([]); // Empty row

        // Add obligation details
        $rows->push(['OBLIGATION DETAILS']);
        $rows->push(['Responsibility Center:', $this->voucher->responsibility_center ?? '---']);
        $rows->push(['Account Code:', $this->voucher->account_code ?? '---']);
        $rows->push(['Particulars Name:', $this->voucher->particulars_name ?? '---']);
        $rows->push([]); // Empty row

        // Add scholars header
        $rows->push(['BENEFICIARIES']);

        // Add scholars data
        foreach ($this->voucher->scholar_ids as $index => $scholarData) {
            if (is_array($scholarData)) {
                $rows->push([
                    $index + 1,
                    ($scholarData['first_name'] ?? '') . ' ' . ($scholarData['last_name'] ?? ''),
                    $scholarData['course_name'] ?? '---',
                    $scholarData['year_level'] ?? '---',
                    $scholarData['academic_year'] ?? '---',
                    $scholarData['term'] ?? '---',
                    $scholarData['scholarship_record_id'] ?? '---'
                ]);
            }
        }

        $rows->push([]); // Empty row

        // Add amounts
        $rows->push(['Per Scholar Amount:', $this->voucher->amount]);
        $rows->push(['Number of Scholars:', count($this->voucher->scholar_ids ?? [])]);
        $rows->push(['TOTAL OBLIGATION:', $this->voucher->amount * count($this->voucher->scholar_ids ?? [])]);

        return $rows;
    }

    /**
     * Set headings
     */
    public function headings(): array
    {
        return ['#', 'Name', 'Course', 'Year Level', 'Academic Year', 'Term', 'Scholarship Record ID'];
    }

    /**
     * Apply styles
     */
    public function styles(Worksheet $sheet)
    {
        // Title styling
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A2:H2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Section headings
        foreach ([6, 12, 19] as $row) {
            if ($sheet->getCellByColumnAndRow(1, $row)->getValue()) {
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE8E8E8');
            }
        }

        return [];
    }
}
