<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Sample of backfilled logs showing initial vs updated status:\n";
echo str_repeat("-", 120) . "\n";

// Get mix of different remarks to show variation
$logs = DB::table('activity_logs')
    ->where('activity_type', 'status_change')
    ->join('users', 'activity_logs.user_id', '=', 'users.id')
    ->select('users.name', 'activity_logs.action', 'activity_logs.remarks')
    ->orderByRaw('RAND()')
    ->limit(15)
    ->get();

$initial = 0;
$updated = 0;

foreach ($logs as $log) {
    echo "User: " . str_pad($log->name, 20) . " | Status: " . str_pad($log->action, 12) . " | " . $log->remarks . "\n";
    if (strpos($log->remarks, 'Initial') !== false) {
        $initial++;
    } else {
        $updated++;
    }
}

echo "\n" . str_repeat("-", 120) . "\n";
echo "Distribution: Initial = $initial, Updated = $updated\n\n";

// Get overall distribution
$distribution = DB::table('activity_logs')
    ->where('activity_type', 'status_change')
    ->select(DB::raw("CASE WHEN remarks LIKE 'Initial%' THEN 'Initial' ELSE 'Updated' END as change_type"), DB::raw('COUNT(*) as count'))
    ->groupBy('change_type')
    ->get();

echo "Overall distribution:\n";
foreach ($distribution as $row) {
    echo $row->change_type . ": " . $row->count . " logs\n";
}
