<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ScholarshipProfile;
use App\Models\ScholarshipRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:activities';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Backfill activity logs with all previous activities (profile updates, file uploads, record changes)';

    protected $systemUserId = 1; // Default system user ID

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting activity logs backfill...');
        $this->line('');

        try {
            // Get system/admin user
            $systemUser = User::first();
            if ($systemUser) {
                $this->systemUserId = $systemUser->id;
            }

            $count = 0;
            $count += $this->backfillProfileCreations();
            $count += $this->backfillProfileUpdates();
            $count += $this->backfillRecordCreations();
            $count += $this->backfillRecordUpdates();
            $count += $this->backfillAttachmentUploads();

            $this->line('');
            $this->info("✅ Backfill completed! Total activities created: $count");
        } catch (\Exception $e) {
            $this->error('Error during backfill: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Backfill profile creation activities
     */
    private function backfillProfileCreations()
    {
        $this->info('📝 Backfilling profile creations...');

        $profiles = ScholarshipProfile::all();
        $count = 0;

        foreach ($profiles as $profile) {
            // Check if activity already exists
            $exists = ActivityLog::where('profile_id', $profile->profile_id)
                ->where('activity_type', 'profile_created')
                ->first();

            if (!$exists) {
                // Use created_by if available, otherwise fall back to system user
                $userId = $profile->created_by ?? $this->systemUserId;

                ActivityLog::logActivity(
                    profileId: $profile->profile_id,
                    userId: $userId,
                    activityType: 'profile_created',
                    action: 'Profile Created',
                    description: "Profile created for {$profile->first_name} {$profile->last_name}",
                    details: [
                        'first_name' => $profile->first_name,
                        'last_name' => $profile->last_name,
                        'email' => $profile->email
                    ],
                    remarks: 'Historical record - auto-backfilled',
                    performedAt: $profile->created_at
                );
                $count++;
            }
        }

        $this->line("   ✓ Created $count profile creation logs");
        return $count;
    }

    /**
     * Backfill profile update activities (if updated_at differs from created_at)
     */
    private function backfillProfileUpdates()
    {
        $this->info('✏️  Backfilling profile updates...');

        $profiles = ScholarshipProfile::whereColumn('updated_at', '!=', 'created_at')->get();
        $count = 0;

        foreach ($profiles as $profile) {
            // Check if update activity already exists
            $exists = ActivityLog::where('profile_id', $profile->profile_id)
                ->where('activity_type', 'profile_updated')
                ->first();

            if (!$exists) {
                // Use updated_by if available, otherwise fall back to system user
                $userId = $profile->updated_by ?? $this->systemUserId;

                ActivityLog::logActivity(
                    profileId: $profile->profile_id,
                    userId: $userId,
                    activityType: 'profile_updated',
                    action: 'Profile Updated',
                    description: "Profile information was updated",
                    details: [
                        'contact_no' => $profile->contact_no,
                        'email' => $profile->email,
                        'address' => $profile->address
                    ],
                    remarks: 'Historical record - auto-backfilled',
                    performedAt: $profile->updated_at
                );
                $count++;
            }
        }

        $this->line("   ✓ Created $count profile update logs");
        return $count;
    }

    /**
     * Backfill scholarship record creation activities
     */
    private function backfillRecordCreations()
    {
        $this->info('📚 Backfilling scholarship record creations...');

        $records = ScholarshipRecord::with('profile')->get();
        $count = 0;

        foreach ($records as $record) {
            $exists = ActivityLog::where('profile_id', $record->profile_id)
                ->where('activity_type', 'record_created')
                ->where('details->record_id', $record->id)
                ->first();

            if (!$exists) {
                // Use created_by if available, otherwise fall back to system user
                $userId = $record->created_by ?? $this->systemUserId;

                ActivityLog::logActivity(
                    profileId: $record->profile_id,
                    userId: $userId,
                    activityType: 'record_created',
                    action: 'Scholarship Record Created',
                    description: "Scholarship record created: {$record->program?->name} - {$record->academic_year}",
                    details: [
                        'record_id' => $record->id,
                        'program' => $record->program?->name,
                        'school' => $record->school?->name,
                        'academic_year' => $record->academic_year,
                        'term' => $record->term
                    ],
                    remarks: 'Historical record - auto-backfilled',
                    performedAt: $record->created_at
                );
                $count++;
            }
        }

        $this->line("   ✓ Created $count scholarship record creation logs");
        return $count;
    }

    /**
     * Backfill scholarship record update activities
     */
    private function backfillRecordUpdates()
    {
        $this->info('🔄 Backfilling scholarship record updates...');

        $records = ScholarshipRecord::whereColumn('updated_at', '!=', 'created_at')->with('profile')->get();
        $count = 0;

        foreach ($records as $record) {
            $exists = ActivityLog::where('profile_id', $record->profile_id)
                ->where('activity_type', 'record_updated')
                ->where('details->record_id', $record->id)
                ->first();

            if (!$exists) {
                // Use updated_by if available, otherwise fall back to system user
                $userId = $record->updated_by ?? $this->systemUserId;

                ActivityLog::logActivity(
                    profileId: $record->profile_id,
                    userId: $userId,
                    activityType: 'record_updated',
                    action: 'Scholarship Record Updated',
                    description: "Scholarship record updated: {$record->program?->name}",
                    details: [
                        'record_id' => $record->id,
                        'status' => $record->unified_status,
                        'year_level' => $record->year_level
                    ],
                    remarks: 'Historical record - auto-backfilled',
                    performedAt: $record->updated_at
                );
                $count++;
            }
        }

        $this->line("   ✓ Created $count scholarship record update logs");
        return $count;
    }

    /**
     * Backfill attachment upload activities
     */
    private function backfillAttachmentUploads()
    {
        $this->info('📎 Backfilling attachment uploads...');

        // Get attachments from the database with related record and profile info
        $attachments = DB::table('scholarship_record_attachments')
            ->join('scholarship_records', 'scholarship_record_attachments.scholarship_record_id', '=', 'scholarship_records.id')
            ->select('scholarship_record_attachments.*', 'scholarship_records.profile_id', 'scholarship_records.created_by')
            ->get();

        $count = 0;

        foreach ($attachments as $attachment) {
            $exists = ActivityLog::where('profile_id', $attachment->profile_id)
                ->where('activity_type', 'attachment_uploaded')
                ->where('details->attachment_id', $attachment->attachment_id)
                ->first();

            if (!$exists) {
                // Use the scholarship record's created_by if available, otherwise system user
                $userId = $attachment->created_by ?? $this->systemUserId;

                ActivityLog::logActivity(
                    profileId: $attachment->profile_id,
                    userId: $userId,
                    activityType: 'attachment_uploaded',
                    action: 'Attachment Uploaded',
                    description: "Attachment uploaded: {$attachment->attachment_name}",
                    details: [
                        'attachment_id' => $attachment->attachment_id,
                        'file_name' => $attachment->file_name,
                        'attachment_name' => $attachment->attachment_name,
                        'file_size' => $attachment->file_size
                    ],
                    remarks: 'Historical record - auto-backfilled',
                    performedAt: $attachment->created_at
                );
                $count++;
            }
        }

        $this->line("   ✓ Created $count attachment upload logs");
        return $count;
    }
}
