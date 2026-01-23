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
     * Log application status change
     */
    public static function logStatusChange($profileId, $oldStatus, $newStatus, $remarks = null)
    {
        ActivityLog::logActivity(
            profileId: $profileId,
            userId: Auth::id(),
            activityType: 'status_change',
            action: $newStatus,
            description: "Changed application status from {$oldStatus} to {$newStatus}",
            oldValue: $oldStatus,
            newValue: $newStatus,
            remarks: $remarks
        );
    }

    /**
     * Log priority assigned
     */
    public static function logPriorityAssigned($profileId, $priorityLevel = null, $remarks = null)
    {
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
