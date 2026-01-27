<?php

namespace Database\Seeders;

use App\Models\ResponsibilityCenter;
use App\Models\Particular;
use Illuminate\Database\Seeder;

class ResponsibilityCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample responsibility centers with particulars
        $rc1 = ResponsibilityCenter::create([
            'code' => 'SC001',
            'name' => 'Scholarship Center',
        ]);

        Particular::create([
            'responsibility_center_id' => $rc1->id,
            'name' => 'Tuition Fee',
            'account_code' => '5010-001',
        ]);

        Particular::create([
            'responsibility_center_id' => $rc1->id,
            'name' => 'Living Allowance',
            'account_code' => '5010-002',
        ]);

        $rc2 = ResponsibilityCenter::create([
            'code' => 'ADM001',
            'name' => 'Administration Office',
        ]);

        Particular::create([
            'responsibility_center_id' => $rc2->id,
            'name' => 'Office Supplies',
            'account_code' => '6010-001',
        ]);

        Particular::create([
            'responsibility_center_id' => $rc2->id,
            'name' => 'Personnel Expenses',
            'account_code' => '6020-001',
        ]);
    }
}
