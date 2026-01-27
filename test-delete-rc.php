<?php
// Test script to verify RC deletion

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ResponsibilityCenter;

// Check if there are any RCs
$rcs = ResponsibilityCenter::all();
echo "Total RCs: " . count($rcs) . "\n";

if (count($rcs) > 0) {
    $rc = $rcs->first();
    echo "Testing delete of RC ID: " . $rc->id . " - " . $rc->name . "\n";
    echo "Particulars count: " . $rc->particulars()->count() . "\n";

    try {
        // Try to delete
        $rc->particulars()->delete();
        $rc->delete();
        echo "Successfully deleted!\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No responsibility centers found\n";
}
