<?php

namespace App\Services;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipApprovalHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScholarshipApprovalService
{
    public function approve(ScholarshipRecord $record, User $approver, array $data = [])
    {
        $this->validateCanModify($record);

        DB::transaction(function () use ($record, $approver, $data) {
            $oldStatus = $record->unified_status;

            $record->update([
                'unified_status' => 'approved',
            ]);

            $this->createStatusHistory($record, 'approved', $oldStatus, $approver, $data['remarks'] ?? null);

            Log::info('Scholarship approved', [
                'record_id' => $record->id,
                'approved_by' => $approver->id,
                'auto_approved' => $data['auto_approved'] ?? false
            ]);
        });
    }

    public function decline(ScholarshipRecord $record, User $decliner, array $data)
    {
        $this->validateCanModify($record);
        $this->validateDeclineReason($data['reason']);

        DB::transaction(function () use ($record, $decliner, $data) {
            $oldStatus = $record->unified_status;

            $record->update([
                'unified_status' => 'denied',
            ]);

            $this->createStatusHistory($record, 'declined', $oldStatus, $decliner, $data['details'] ?? null);

            Log::info('Scholarship declined', [
                'record_id' => $record->id,
                'declined_by' => $decliner->id,
                'reason' => $data['reason']
            ]);
        });
    }

    public function setConditional(ScholarshipRecord $record, User $user, array $requirements)
    {
        throw new \InvalidArgumentException('Conditional approval workflow is no longer supported. Use approve() or decline() instead.');
    }

    /**
     * Update conditional approval deadline and conditions (DEPRECATED)
     * Conditional approval is no longer supported
     */
    public function updateConditional(ScholarshipRecord $record, User $user, array $updates)
    {
        throw new \InvalidArgumentException('Conditional approval workflow is no longer supported. Use approve() or decline() instead.');
    }

    private function _updateConditionalDeprecated(ScholarshipRecord $record, User $user, array $updates)
    {
        DB::transaction(function () use ($record, $user, $updates) {
            $oldDeadline = $record->conditional_deadline;
            $oldConditions = $record->conditional_requirements;

            $updateData = [];

            // Update deadline if provided
            if (isset($updates['deadline'])) {
                $updateData['conditional_deadline'] = $updates['deadline'];
                // Reset notification flag if deadline changes
                if ($updates['deadline'] != $oldDeadline) {
                    $updateData['conditional_deadline_notified_at'] = null;
                    $updateData['conditional_deadline_expired'] = false;
                }
            }

            // Update conditions if provided
            if (isset($updates['conditions'])) {
                $updateData['conditional_requirements'] = $updates['conditions'];
            }

            // Note: Update remarks are used only for history tracking, not stored in approval_remarks
            // to preserve original approval remarks

            $record->update($updateData);

            // Create history entry for the update
            $historyRemarks = $this->buildUpdateHistoryRemarks($oldDeadline, $oldConditions, $updates);
            $this->createStatusHistory($record, 'conditional_updated', 'conditional', $user, $historyRemarks);

            Log::info('Conditional approval updated', [
                'record_id' => $record->id,
                'updated_by' => $user->id,
                'old_deadline' => $oldDeadline,
                'new_deadline' => $updates['deadline'] ?? $oldDeadline,
                'conditions_changed' => isset($updates['conditions']),
            ]);
        });
    }

    /**
     * Build history remarks for conditional updates
     */
    private function buildUpdateHistoryRemarks($oldDeadline, $oldConditions, $updates)
    {
        $changes = [];

        if (isset($updates['deadline']) && $updates['deadline'] != $oldDeadline) {
            $changes[] = "Deadline changed from " . ($oldDeadline ? $oldDeadline : 'not set') . " to " . $updates['deadline'];
        }

        if (isset($updates['conditions']) && $updates['conditions'] != $oldConditions) {
            $changes[] = "Conditions updated";
        }

        return implode('; ', $changes);
    }

    /**
     * Handle expired conditional approvals (DEPRECATED)
     * Conditional approval is no longer supported
     */
    public function expireConditionalApproval(ScholarshipRecord $record, User $systemUser = null)
    {
        throw new \InvalidArgumentException('Conditional approval workflow is no longer supported. Use decline() instead.');
    }

    /**
     * Send deadline reminder notification (DEPRECATED)
     * No longer applicable with unified status system
     */
    public function sendDeadlineReminder(ScholarshipRecord $record)
    {
        Log::info('sendDeadlineReminder called but deprecated', ['record_id' => $record->id]);
        return false;
    }

    /**
     * Check and process expired conditional approvals (DEPRECATED)
     */
    public function processExpiredConditionalApprovals()
    {
        Log::info('processExpiredConditionalApprovals called but deprecated');
        return 0;
    }

    /**
     * Send deadline reminders (DEPRECATED)
     */
    public function sendUpcomingDeadlineReminders($daysBefore = 3)
    {
        Log::info('sendUpcomingDeadlineReminders called but deprecated');
        return 0;
    }

    /**
     * Get or create system user for automated actions
     */
    private function getSystemUser()
    {
        return User::firstOrCreate([
            'username' => 'system'
        ], [
            'name' => 'System User',
            'password' => bcrypt('system-generated-password'),
        ]);
    }

    /**
     * Resubmit declined application (DEPRECATED)
     */
    public function resubmit(ScholarshipRecord $record, array $data = [])
    {
        throw new \InvalidArgumentException('Resubmission workflow is no longer supported.');
    }

    public function autoApprove(ScholarshipRecord $record)
    {
        if (!$record->shouldAutoApprove()) {
            return false;
        }

        try {
            // Create a system user for auto-approvals
            $systemUser = User::where('email', 'system@scholarship.local')->first();
            if (!$systemUser) {
                // Create system user if it doesn't exist
                $systemUser = User::create([
                    'name' => 'System Auto-Approval',
                    'email' => 'system@scholarship.local',
                    'password' => bcrypt('system_auto_approval_' . uniqid()),
                    'email_verified_at' => now()
                ]);
            }

            $this->approve($record, $systemUser, [
                'remarks' => 'Auto-approved by system based on configuration',
                'auto_approved' => true
            ]);

            Log::info('Scholarship auto-approved', [
                'record_id' => $record->id,
                'trigger' => 'resubmission'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Auto-approval failed', [
                'record_id' => $record->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function processNewApplication(ScholarshipRecord $record)
    {
        // Auto-approve new applications if configured
        $autoApprovalConfig = config('scholarship.auto_approval');

        if ($autoApprovalConfig['enabled'] && $autoApprovalConfig['conditions']['new_applications']) {
            $this->autoApprove($record);
        }
    }

    private function validateCanModify(ScholarshipRecord $record)
    {
        if (!$record->canBeModified()) {
            throw new \InvalidArgumentException("Application with status '{$record->unified_status}' cannot be modified");
        }
    }

    private function validateDeclineReason(string $reason)
    {
        $validReasons = array_keys(config('scholarship.decline_reasons'));

        if (!in_array($reason, $validReasons)) {
            throw new \InvalidArgumentException("Invalid decline reason: {$reason}");
        }
    }

    private function createStatusHistory(ScholarshipRecord $record, string $action, string $oldStatus, ?User $user, ?string $remarks)
    {
        ScholarshipApprovalHistory::create([
            'scholarship_record_id' => $record->id,
            'action' => $action,
            'previous_status' => $oldStatus,
            'new_status' => $record->unified_status,
            'performed_by' => $user?->id,
            'remarks' => $remarks,
            'performed_at' => now(),
        ]);
    }

    public function getApprovalStats(array $filters = [])
    {
        $query = ScholarshipRecord::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->where('unified_status', 'pending')->count(),
            'approved' => $query->clone()->where('unified_status', 'approved')->count(),
            'denied' => $query->clone()->where('unified_status', 'denied')->count(),
            'active' => $query->clone()->where('unified_status', 'active')->count(),
            'completed' => $query->clone()->where('unified_status', 'completed')->count(),
        ];
    }
}
