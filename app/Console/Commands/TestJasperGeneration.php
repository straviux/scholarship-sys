<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JasperReportService;

class TestJasperGeneration extends Command
{
    protected $signature = 'jasper:generate-test';
    protected $description = 'Test JasperReports generation';

    public function handle(JasperReportService $jasperService)
    {
        $this->info('🧪 Testing JasperReports Report Generation...');
        $this->newLine();

        try {
            $this->info('1️⃣  Generating test report (PDF)...');

            $pdfPath = $jasperService
                ->template('waiting_list')
                ->format('pdf')
                ->generate();

            $this->info('✅ PDF generated successfully!');
            $this->line("   Path: $pdfPath");

            if (file_exists($pdfPath)) {
                $fileSize = filesize($pdfPath) / 1024;
                $this->line("   Size: " . number_format($fileSize, 2) . " KB");
            }

            $this->newLine();
            $this->info('2️⃣  Generating test report (HTML)...');

            $htmlPath = $jasperService
                ->template('waiting_list')
                ->format('html')
                ->generate();

            $this->info('✅ HTML generated successfully!');
            $this->line("   Path: $htmlPath");

            $this->newLine();
            $this->info('✨ JasperReports is working perfectly!');
            $this->info('🎉 Setup complete and tested successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line('');
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
