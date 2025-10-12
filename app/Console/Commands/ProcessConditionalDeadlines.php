<?php

namespace App\Console\Commands;

use App\Services\ScholarshipApprovalService;
use Illuminate\Console\Command;

class ProcessConditionalDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scholarship:process-conditional-deadlines
                            {--send-reminders : Send deadline reminders}
                            {--reminder-days=3 : Days before deadline to send reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired conditional approvals and send deadline reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $approvalService = app(ScholarshipApprovalService::class);

        // Process expired conditional approvals
        $this->info('Processing expired conditional approvals...');
        $expiredCount = $approvalService->processExpiredConditionalApprovals();
        $this->info("Processed {$expiredCount} expired conditional approvals.");

        // Send deadline reminders if requested
        if ($this->option('send-reminders')) {
            $reminderDays = (int) $this->option('reminder-days');
            $this->info("Sending deadline reminders ({$reminderDays} days before deadline)...");
            $reminderCount = $approvalService->sendUpcomingDeadlineReminders($reminderDays);
            $this->info("Sent {$reminderCount} deadline reminders.");
        }

        $this->info('Conditional deadline processing completed.');

        return Command::SUCCESS;
    }
}
