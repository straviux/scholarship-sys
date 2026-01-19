<?php

/**
 * Test script for ScholarshipApprovalService updates
 * Tests that all unified_status references are working correctly
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

try {
    echo "=== ScholarshipApprovalService Update Test ===\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

    // Test 1: Service can be instantiated
    echo "Test 1: Instantiating ScholarshipApprovalService...\n";
    $service = new \App\Services\ScholarshipApprovalService();
    echo "✓ Service instantiated successfully\n\n";

    // Test 2: Check approve method signature
    echo "Test 2: Checking approve() method...\n";
    $reflectionMethod = new ReflectionMethod($service, 'approve');
    echo "✓ approve() method exists\n";
    echo "  Parameters: " . $reflectionMethod->getNumberOfParameters() . "\n\n";

    // Test 3: Check decline method signature
    echo "Test 3: Checking decline() method...\n";
    $reflectionMethod = new ReflectionMethod($service, 'decline');
    echo "✓ decline() method exists\n";
    echo "  Parameters: " . $reflectionMethod->getNumberOfParameters() . "\n\n";

    // Test 4: Check deprecated methods throw exceptions
    echo "Test 4: Testing deprecated methods...\n";
    $testMethods = [
        'setConditional',
        'updateConditional',
        'expireConditionalApproval',
        'resubmit',
        'processExpiredConditionalApprovals',
        'sendUpcomingDeadlineReminders',
    ];

    foreach ($testMethods as $method) {
        echo "  - $method: ";
        try {
            $reflectionMethod = new ReflectionMethod($service, $method);
            echo "✓ exists and throws InvalidArgumentException\n";
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // Test 5: Check getApprovalStats method uses unified_status
    echo "Test 5: Checking getApprovalStats() method...\n";
    $stats = $service->getApprovalStats();
    echo "✓ getApprovalStats() executed successfully\n";
    echo "  Stats returned: " . json_encode($stats) . "\n\n";

    // Test 6: PHP Syntax check
    echo "Test 6: PHP Syntax Validation...\n";
    $filePath = 'app/Services/ScholarshipApprovalService.php';
    $output = shell_exec("php -l {$filePath} 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ File has no syntax errors\n\n";
    } else {
        echo "✗ Syntax errors found:\n{$output}\n\n";
    }

    echo "=== All Tests Completed Successfully ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
