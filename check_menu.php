<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check if is_group column exists
if (Schema::hasColumn('menu_items', 'is_group')) {
    echo "✓ is_group column EXISTS\n";
} else {
    echo "✗ is_group column MISSING\n";
}

// Get all columns
$columns = Schema::getColumnListing('menu_items');
echo "\nAll columns in menu_items:\n";
foreach ($columns as $col) {
    echo "  - " . $col . "\n";
}

// Check sample data
echo "\n\nSample menu items:\n";
$items = DB::table('menu_items')->limit(3)->get();
foreach ($items as $item) {
    echo "\nItem: " . $item->name . "\n";
    echo "  is_group: " . (isset($item->is_group) ? $item->is_group : 'MISSING') . "\n";
    echo "  is_active: " . $item->is_active . "\n";
}

echo "\n\nTotal items: " . DB::table('menu_items')->count() . "\n";
