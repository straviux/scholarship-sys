<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ScholarshipRecord;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillApprovalHistory extends Command
{
    protected $signature = 'backfill:approval-history';

    protected $description = 'Backfill approval history with unified status changes for scholarship records';

    public function handle()
    {
        $this->info('Starting approval history backfill...');
        $this->line('');

        try {
            $count = 0;
            $records = ScholarshipRecord::with('profile', 'program')->get();

            $this->withProgressBar($records, function ($record) use (&$count) {
                $profileId = $record->profile_id;

                if (!$profileId || !$record->unified_status) {
                    return;
                }

                // Check if this record already has a status log
                $exists = ActivityLog::where('profile_id', $profileId)
                    ->where('activity_type', 'status_changed')
                    ->where('details->record_id', $record->id)
                    ->exists();

                if (!$exists) {
                    // Use the user who created the record
                    $userId = $record->created_by;

                    // All records marked as initially encoded
                    $remarks = "Record initially encoded as {$record->unified_status}";
                    $description = "Record initially encoded as {$record->unified_status}";

                    // Always use created_at timestamp
                    $performedAt = $record->created_at;

                    ActivityLog::create([
                        'profile_id' => $profileId,
                        'user_id' => $userId,
                        'activity_type' => 'status_changed',
                        'action' => $record->unified_status,
                        'description' => $description,
                        'new_value' => $record->unified_status,
                        'remarks' => $remarks,
                        'details' => [
                            'record_id' => $record->id,
                            'program_name' => $record->program?->name ?? 'Unknown Program',
                            'academic_year' => $record->academic_year
                        ],
                        'performed_at' => $performedAt,
                    ]);

                    $count++;
                }
            });

            $this->newLine();
            $this->info("✅ Approval history backfill completed! Created $count status logs.");
        } catch (\Exception $e) {
            $this->error('Error during backfill: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
