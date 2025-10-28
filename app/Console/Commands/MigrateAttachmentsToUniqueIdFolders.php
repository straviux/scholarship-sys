<?php

namespace App\Console\Commands;

use App\Models\DisbursementAttachment;
use App\Models\ScholarshipRecordAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAttachmentsToUniqueIdFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:migrate-to-unique-id-folders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing attachments to unique_id folder structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of attachments to unique_id folder structure...');

        // Migrate disbursement attachments
        $this->info("\n=== Migrating Disbursement Attachments ===");
        $this->migrateDisbursementAttachments();

        // Migrate scholarship record attachments
        $this->info("\n=== Migrating Scholarship Record Attachments ===");
        $this->migrateScholarshipRecordAttachments();

        // Cleanup empty old directories
        $this->info("\n=== Cleaning up empty directories ===");
        $this->cleanupEmptyDirectories();

        $this->newLine();
        $this->info('All migrations completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Migrate disbursement attachments
     */
    private function migrateDisbursementAttachments()
    {
        $attachments = DisbursementAttachment::with('disbursement.profile')->get();
        $totalCount = $attachments->count();
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $this->info("Found {$totalCount} attachments to process.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        foreach ($attachments as $attachment) {
            try {
                // Get the profile
                $profile = $attachment->disbursement->profile;

                if (!$profile || !$profile->unique_id) {
                    $this->newLine();
                    $this->warn("Skipping attachment ID {$attachment->attachment_id}: No profile or unique_id found");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                $uniqueId = $profile->unique_id;
                $oldPath = $attachment->file_path;

                // Check if file exists
                if (!Storage::disk('public')->exists($oldPath)) {
                    $this->newLine();
                    $this->warn("Skipping attachment ID {$attachment->attachment_id}: File not found at {$oldPath}");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Check if already in new structure (attachments/[unique_id]/)
                if (str_starts_with($oldPath, "attachments/{$uniqueId}/")) {
                    $this->newLine();
                    $this->info("Skipping attachment ID {$attachment->attachment_id}: Already in new structure");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Parse filename from old path
                $fileName = basename($oldPath);

                // Update filename to include module prefix if not already present
                if (!str_starts_with($fileName, 'disbursement_')) {
                    // Extract parts from old filename format: [scholar_name]_[attachment_type]_[timestamp].[extension]
                    $parts = explode('_', pathinfo($fileName, PATHINFO_FILENAME));
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                    // Reconstruct with disbursement prefix
                    if (count($parts) >= 3) {
                        // Last part is timestamp, second to last is attachment type
                        $timestamp = array_pop($parts);
                        $attachmentType = array_pop($parts);
                        $scholarName = implode('_', $parts);

                        $fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
                    } else {
                        // If format doesn't match, just add prefix
                        $fileName = "disbursement_{$fileName}";
                    }
                }

                // New path: attachments/[unique_id]/[filename]
                $newPath = "attachments/{$uniqueId}/{$fileName}";

                // Ensure directory exists
                Storage::disk('public')->makeDirectory("attachments/{$uniqueId}");

                // Copy file to new location
                if (Storage::disk('public')->copy($oldPath, $newPath)) {
                    // Update database record
                    $attachment->update([
                        'file_path' => $newPath,
                        'file_name' => $fileName
                    ]);

                    // Delete old file
                    Storage::disk('public')->delete($oldPath);

                    $successCount++;
                } else {
                    $this->newLine();
                    $this->error("Failed to copy file for attachment ID {$attachment->attachment_id}");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing attachment ID {$attachment->attachment_id}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Disbursement Attachments Migration Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failedCount],
                ['Skipped', $skippedCount],
                ['Total', $totalCount]
            ]
        );
    }

    /**
     * Migrate scholarship record attachments
     */
    private function migrateScholarshipRecordAttachments()
    {
        $attachments = ScholarshipRecordAttachment::with('scholarshipRecord.profile')->get();
        $totalCount = $attachments->count();
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $this->info("Found {$totalCount} scholarship record attachments to process.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        foreach ($attachments as $attachment) {
            try {
                // Get the profile
                $profile = $attachment->scholarshipRecord->profile;

                if (!$profile || !$profile->unique_id) {
                    $this->newLine();
                    $this->warn("Skipping attachment ID {$attachment->id}: No profile or unique_id found");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                $uniqueId = $profile->unique_id;
                $oldPath = $attachment->file_path;

                // Check if file exists
                if (!Storage::disk('public')->exists($oldPath)) {
                    $this->newLine();
                    $this->warn("Skipping attachment ID {$attachment->id}: File not found at {$oldPath}");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Check if already in new structure (attachments/[unique_id]/)
                if (str_starts_with($oldPath, "attachments/{$uniqueId}/")) {
                    $this->newLine();
                    $this->info("Skipping attachment ID {$attachment->id}: Already in new structure");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Parse filename from old path
                $fileName = basename($oldPath);

                // Update filename to include module prefix if not already present
                if (!str_starts_with($fileName, 'scholarship_record_')) {
                    // Extract parts from old filename format
                    $parts = explode('_', pathinfo($fileName, PATHINFO_FILENAME));
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                    // Reconstruct with scholarship_record prefix
                    if (count($parts) >= 3) {
                        $fileName = "scholarship_record_{$fileName}";
                    } else {
                        // If format doesn't match, just add prefix
                        $fileName = "scholarship_record_{$fileName}";
                    }
                }

                // New path: attachments/[unique_id]/[filename]
                $newPath = "attachments/{$uniqueId}/{$fileName}";

                // Ensure directory exists
                Storage::disk('public')->makeDirectory("attachments/{$uniqueId}");

                // Copy file to new location
                if (Storage::disk('public')->copy($oldPath, $newPath)) {
                    // Update database record
                    $attachment->update([
                        'file_path' => $newPath,
                        'file_name' => $fileName
                    ]);

                    // Delete old file
                    Storage::disk('public')->delete($oldPath);

                    $successCount++;
                } else {
                    $this->newLine();
                    $this->error("Failed to copy file for attachment ID {$attachment->id}");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing attachment ID {$attachment->id}: " . $e->getMessage());
                $failedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Scholarship Record Attachments Migration Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failedCount],
                ['Skipped', $skippedCount],
                ['Total', $totalCount]
            ]
        );
    }

    /**
     * Clean up empty directories in old structure
     */
    private function cleanupEmptyDirectories()
    {
        $oldDirs = [
            'disbursements/attachments',
            'disbursements',
            'scholarship_records/attachments',
            'scholarship_records'
        ];

        foreach ($oldDirs as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $files = Storage::disk('public')->files($dir);
                $subDirs = Storage::disk('public')->directories($dir);

                if (empty($files) && empty($subDirs)) {
                    Storage::disk('public')->deleteDirectory($dir);
                    $this->info("Removed empty directory: {$dir}");
                }
            }
        }
    }
}
