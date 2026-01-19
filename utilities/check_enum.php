<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select("SHOW COLUMNS FROM scholarship_records LIKE 'grant_provision'");

if (!empty($result)) {
    echo "Column: grant_provision\n";
    echo "Type: " . $result[0]->Type . "\n";
    echo "Null: " . $result[0]->Null . "\n";
    echo "Default: " . $result[0]->Default . "\n";
} else {
    echo "Column not found\n";
}
