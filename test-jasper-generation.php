<?php

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $jasperService = app(\App\Services\JasperReportService::class);

    echo "🧪 Testing JasperReports Report Generation...\n\n";

    echo "1. Generating test report...\n";
    $result = $jasperService
        ->template('reports', 'test-report')
        ->format('pdf')
        ->generate();

    echo "✅ Report generated successfully!\n";
    echo "Output path: " . $result . "\n\n";

    if (file_exists($result)) {
        $fileSize = filesize($result) / 1024;
        echo "📄 File size: " . number_format($fileSize, 2) . " KB\n";
        echo "✨ JasperReports is working perfectly!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getTraceAsString')) {
        echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    }
    exit(1);
}
