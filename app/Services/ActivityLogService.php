<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log profile edited activity
     */
    public static function logProfileEdited($profileId, $changes = [], $remarks = null)
    {
        $details = [];
        $changedFields = [];

        foreach ($changes as $field => $change) {
            $changedFields[] = $field;
            if (is_array($change)) {
                $details[$field] = [
                    'old' => $change['old'] ?? null,
                    'new' => $change['new'] ?? null
                ];
            }
        }

        // Autogenerate remarks if not provided
        if (!$remarks && !empty($changedFields)) {
            $remarks = "Modified " . count($changedFields) . " field(s): " . implode(", ", array_slice($changedFields, 0, 3));
            if (count($changedFields) > 3) {
                $remarks .= " and " . (count($changedFields) - 3) . " more";
            }
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'profile_edited',
            action: 'updated',
            description: 'Edited applicant profile: ' . implode(', ', $changedFields),
            details: $details,
            remarks: $remarks
        );
    }

    /**
     * Log scholarship record created
     */
    public static function logRecordCreated($profileId, $recordData = [], $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $program = $recordData['program_name'] ?? 'Unknown Program';
            $year = $recordData['academic_year'] ?? 'N/A';
            $term = $recordData['term'] ?? 'N/A';
            $remarks = "Created scholarship record for {$program} ({$year}, {$term})";
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'record_created',
            action: 'created',
            description: 'Created scholarship record',
            details: [
                'program' => $recordData['program_name'] ?? null,
                'academic_year' => $recordData['academic_year'] ?? null,
                'term' => $recordData['term'] ?? null
            ],
            remarks: $remarks
        );
    }

    /**
     * Log scholarship record updated
     */
    public static function logRecordUpdated($profileId, $oldData = [], $newData = [], $remarks = null)
    {
        $changes = [];
        $details = [];

        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = $field;
                $details[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if (count($changes) > 0) {
            // Autogenerate remarks if not provided
            if (!$remarks) {
                $remarks = "Updated " . count($changes) . " field(s): " . implode(", ", array_slice($changes, 0, 3));
                if (count($changes) > 3) {
                    $remarks .= " and " . (count($changes) - 3) . " more";
                }
            }

            ActivityLog::logActivity(
                profileId: $profileId,
                userId: Auth::id(),
                activityType: 'record_updated',
                action: 'updated',
                description: 'Updated scholarship record: ' . implode(', ', $changes),
                details: $details,
                remarks: $remarks
            );
        }
    }

    /**
     * Log scholarship record deleted
     */
    public static function logRecordDeleted($profileId, $recordData = [], $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $program = $recordData['program_name'] ?? 'Unknown Program';
            $year = $recordData['academic_year'] ?? 'N/A';
            $remarks = "Deleted scholarship record for {$program} ({$year})";
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'record_deleted',
            action: 'deleted',
            description: 'Deleted scholarship record',
            details: [
                'program' => $recordData['program_name'] ?? null,
                'academic_year' => $recordData['academic_year'] ?? null,
                'term' => $recordData['term'] ?? null
            ],
            remarks: $remarks
        );
    }

    /**
     * Log attachment uploaded
     */
    public static function logAttachmentUploaded($profileId, $attachmentName = null, $fileName = null, $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "Uploaded {$attachmentName}";
            if ($fileName) {
                $remarks .= " ({$fileName})";
            }
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'attachment_uploaded',
            action: 'uploaded',
            description: "Uploaded attachment: {$attachmentName}",
            details: [
                'attachment_name' => $attachmentName,
                'file_name' => $fileName
            ],
            remarks: $remarks
        );
    }

    /**
     * Log attachment deleted
     */
    public static function logAttachmentDeleted($profileId, $attachmentName = null, $fileName = null, $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "Deleted {$attachmentName}";
            if ($fileName) {
                $remarks .= " ({$fileName})";
            }
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'attachment_deleted',
            action: 'deleted',
            description: "Deleted attachment: {$attachmentName}",
            details: [
                'attachment_name' => $attachmentName,
                'file_name' => $fileName
            ],
            remarks: $remarks
        );
    }

    /**
     * Log application status change - only logs if status actually changed
     */
    public static function logStatusChange($profileId, $oldStatus, $newStatus, $remarks = null)
    {
        // Only log if status actually changed
        if ($oldStatus === $newStatus) {
            return false; // Status didn't change, don't log
        }

        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "Status changed from {$oldStatus} to {$newStatus}";
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'status_changed',
            action: $newStatus,
            description: "Changed application status from {$oldStatus} to {$newStatus}",
            oldValue: $oldStatus,
            newValue: $newStatus,
            remarks: $remarks
        );

        return true; // Status changed and was logged
    }

    /**
     * Log priority assigned
     */
    public static function logPriorityAssigned($profileId, $priorityLevel = null, $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "Assigned priority level: {$priorityLevel}";
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'priority_assigned',
            action: 'assigned',
            description: "Assigned priority level: {$priorityLevel}",
            details: ['priority_level' => $priorityLevel],
            remarks: $remarks
        );
    }

    /**
     * Log priority removed
     */
    public static function logPriorityRemoved($profileId, $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "Removed priority level";
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'priority_removed',
            action: 'removed',
            description: "Removed priority level",
            remarks: $remarks
        );
    }

    /**
     * Log YAKAP category updated
     */
    public static function logYakapCategoryUpdated($profileId, $oldCategory, $newCategory, $location = null, $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            $remarks = "YAKAP category changed from {$oldCategory} to {$newCategory}";
            if ($location) {
                $remarks .= " for {$location}";
            }
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'yakap_updated',
            action: 'updated',
            description: "Updated YAKAP category from {$oldCategory} to {$newCategory}",
            oldValue: $oldCategory,
            newValue: $newCategory,
            details: ['location' => $location],
            remarks: $remarks
        );
    }

    /**
     * Log JPM tagging
     */
    public static function logJpmTagging($profileId, $changes = [], $remarks = null)
    {
        // Autogenerate remarks if not provided
        if (!$remarks) {
            if (count($changes) > 0) {
                $changedFields = array_keys($changes);
                $remarks = "Updated JPM tagging: " . implode(", ", array_slice($changedFields, 0, 3));
                if (count($changedFields) > 3) {
                    $remarks .= " and " . (count($changedFields) - 3) . " more";
                }
            } else {
                $remarks = "Updated JPM tagging information";
            }
        }

        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'jpm_tagged',
            action: 'updated',
            description: "Updated JPM tagging information",
            details: $changes,
            remarks: $remarks
        );
    }
}
