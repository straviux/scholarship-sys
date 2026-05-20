<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ScholarshipProfileController;

$controller = new ScholarshipProfileController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getInterviewedApplicantsBudgetAllocations');
$method->setAccessible(true);

$allocations = $method->invoke($controller);

$has_nonzero_estimated = false;
foreach ($allocations as $key => $allocation) {
    $estimated_total = $allocation['approved_scholars_current_ay_estimated_total'] ?? 0;
    if ($estimated_total > 0) {
        $has_nonzero_estimated = true;
        
        $total_allotment = $allocation['total_allotment'] ?? 0;
        $disbursed = $allocation['disbursed'] ?? 0;
        $approved_scholars_to_date = $allocation['approved_scholars_to_date'] ?? 0;
        $running_balance = $total_allotment - $disbursed - $estimated_total;

        echo "Key: $key" . PHP_EOL;
        echo "Total Allotment: $total_allotment" . PHP_EOL;
        echo "Disbursed: $disbursed" . PHP_EOL;
        echo "Approved Scholars to Date: $approved_scholars_to_date" . PHP_EOL;
        echo "Approved Scholars Current AY Est Total: $estimated_total" . PHP_EOL;
        echo "Running Balance: $running_balance" . PHP_EOL;
        echo "-----------------------------------" . PHP_EOL;
    }
}

if (!$has_nonzero_estimated) {
    echo "No allocations found with a nonzero approved_scholars_current_ay_estimated_total." . PHP_EOL;
}
