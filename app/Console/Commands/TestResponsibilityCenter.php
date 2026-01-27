<?php

namespace App\Console\Commands;

use App\Models\ResponsibilityCenter;
use Illuminate\Console\Command;

class TestResponsibilityCenter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-responsibility-center';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test responsibility center functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Responsibility Center...');

        $rcs = ResponsibilityCenter::with('particulars')->get();

        $this->info('Found ' . $rcs->count() . ' responsibility centers');

        foreach ($rcs as $rc) {
            $this->info("- {$rc->code}: {$rc->name} (" . $rc->particulars->count() . " particulars)");
        }

        // Test JSON response
        $data = [
            'success' => true,
            'data' => $rcs->toArray()
        ];

        $this->info('JSON Response:');
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }
}
