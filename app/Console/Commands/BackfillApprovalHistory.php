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
            $records = ScholarshipRecord::with('profile')->get();

            $this->withProgressBar($records, function ($record) use (&$count) {
                $profileId = $record->profile_id;

                if (!$profileId || !$record->unified_status) {
                    return;
                }

                // Check if this record already has a status log
                $exists = ActivityLog::where('profile_id', $profileId)
                    ->where('activity_type', 'status_change')
                    ->where('details->record_id', $record->id)
                    ->exists();

                if (!$exists) {
                    // Use the user who created the record, or who last updated it
                    $userId = $record->created_by ?? $record->updated_by;

                    // Check if this is the initial status (created_at = updated_at means no changes)
                    // or if it was updated later (created_at < updated_at)
                    $isInitial = $record->created_at->eq($record->updated_at);

                    // Autogenerate remarks indicating if it's initial or updated
                    if ($isInitial) {
                        $remarks = "Record initially encoded as {$record->unified_status}";
                        $description = "Record initially encoded as {$record->unified_status}";
                    } else {
                        $remarks = "Status changed to {$record->unified_status}";
                        $description = "Changed application status to {$record->unified_status}";
                    }

                    ActivityLog::create([
                        'profile_id' => $profileId,
                        'user_id' => $userId,
                        'activity_type' => 'status_change',
                        'action' => $record->unified_status,
                        'description' => $description,
                        'new_value' => $record->unified_status,
                        'remarks' => $remarks,
                        'details' => [
                            'record_id' => $record->id,
                            'program_name' => $record->program?->program_name ?? 'Unknown',
                            'academic_year' => $record->academic_year
                        ],
                        'performed_at' => $record->updated_at ?? $record->created_at,
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
