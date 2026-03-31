<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all school-payee vouchers (where scholar_ids has actual data)
echo "=== SCHOOL-PAYEE VOUCHERS WITH SCHOLAR_IDS ===\n";
$schoolVouchers = \App\Models\FundTransaction::whereNotNull('scholar_ids')
    ->whereRaw("JSON_LENGTH(scholar_ids) > 0")
    ->get(['id', 'payee_name', 'scholar_ids', 'payee_type']);

foreach ($schoolVouchers as $v) {
    $names = collect($v->scholar_ids)->pluck('name')->implode(', ');
    echo "ID: {$v->id} | PayeeType: {$v->payee_type} | Payee: {$v->payee_name} | Scholars: {$names}\n";
}

echo "\nTotal school-payee vouchers: " . $schoolVouchers->count() . "\n";

// Now test search with each scholar name
if ($schoolVouchers->count() > 0) {
    $firstSchool = $schoolVouchers->first(fn($v) => $v->payee_type === 'school');
    if ($firstSchool && !empty($firstSchool->scholar_ids)) {
        $scholarName = $firstSchool->scholar_ids[0]['name'] ?? 'N/A';
        $searchTerm = explode(' ', $scholarName)[0]; // first word

        echo "\n=== SEARCH TEST: '$searchTerm' (from school voucher ID {$firstSchool->id}) ===\n";

        DB::enableQueryLog();

        $query = \App\Models\FundTransaction::with('creator')->latest();
        $query->where(function ($q) use ($searchTerm) {
            $q->where('transaction_id', 'like', "%{$searchTerm}%")
                ->orWhere('payee_name', 'like', "%{$searchTerm}%")
                ->orWhere('disbursement_type', 'like', "%{$searchTerm}%")
                ->orWhereHas('creator', fn($q2) => $q2->where('name', 'like', "%{$searchTerm}%"));

            $q->orWhereRaw("JSON_SEARCH(scholar_ids, 'one', ?, NULL, '$[*].name') IS NOT NULL", ['%' . $searchTerm . '%']);
        });

        $results = $query->paginate(10);

        echo "Results: " . $results->total() . "\n";
        foreach ($results as $r) {
            echo "  ID: {$r->id} | Payee: {$r->payee_name}\n";
        }

        $found = $results->contains('id', $firstSchool->id);
        echo "\nSchool voucher ID {$firstSchool->id} found in results: " . ($found ? 'YES' : 'NO') . "\n";
    } else {
        echo "\nNo voucher with payee_type=school found. Checking payee_type values:\n";
        echo $schoolVouchers->pluck('payee_type')->unique()->implode(', ') . "\n";
    }
}
