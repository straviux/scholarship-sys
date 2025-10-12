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
            $oldStatus = $record->approval_status;

            $record->update([
                'approval_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_remarks' => $data['remarks'] ?? null,
                'application_status' => 1, // Update existing status field
                // Clear decline fields if previously declined
                'declined_by' => null,
                'declined_at' => null,
                'decline_reason' => null,
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
            $oldStatus = $record->approval_status;

            $record->update([
                'approval_status' => 'declined',
                'declined_by' => $decliner->id,
                'declined_at' => now(),
                'decline_reason' => $data['reason'],
                'application_status' => 2, // Update existing status field
                // Clear approval fields if previously approved
                'approved_by' => null,
                'approved_at' => null,
                'approval_remarks' => null,
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
        $this->validateCanModify($record);

        DB::transaction(function () use ($record, $user, $requirements) {
            $oldStatus = $record->approval_status;

            $record->update([
                'approval_status' => 'conditional',
                'conditional_requirements' => $requirements['conditions'] ?? [],
                'conditional_deadline' => $requirements['deadline'] ?? null,
                'conditional_deadline_expired' => false,
                'conditional_deadline_notified_at' => null,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_remarks' => $requirements['remarks'] ?? null,
            ]);

            $this->createStatusHistory($record, 'conditional', $oldStatus, $user, $requirements['remarks'] ?? null);

            Log::info('Conditional approval set', [
                'record_id' => $record->id,
                'set_by' => $user->id,
                'deadline' => $requirements['deadline'],
                'conditions' => $requirements['conditions']
            ]);
        });
    }

    /**
     * Update conditional approval deadline and conditions
     */
    public function updateConditional(ScholarshipRecord $record, User $user, array $updates)
    {
        if ($record->approval_status !== 'conditional') {
            throw new \InvalidArgumentException('Only conditional approvals can be updated');
        }

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
     * Handle expired conditional approvals
     */
    public function expireConditionalApproval(ScholarshipRecord $record, User $systemUser = null)
    {
        if ($record->approval_status !== 'conditional') {
            throw new \InvalidArgumentException('Only conditional approvals can be expired');
        }

        $systemUser = $systemUser ?? $this->getSystemUser();

        DB::transaction(function () use ($record, $systemUser) {
            $oldStatus = $record->approval_status;

            $record->update([
                'approval_status' => 'declined',
                'conditional_deadline_expired' => true,
                'declined_by' => $systemUser->id,
                'declined_at' => now(),
                'decline_reason' => 'conditional_deadline_expired',
                'approval_remarks' => 'Conditional approval expired - deadline not met',
            ]);

            $this->createStatusHistory($record, 'declined', $oldStatus, $systemUser, 'Conditional approval deadline expired');

            Log::info('Conditional approval expired', [
                'record_id' => $record->id,
                'deadline' => $record->conditional_deadline,
                'expired_at' => now()
            ]);
        });
    }

    /**
     * Send deadline reminder notification
     */
    public function sendDeadlineReminder(ScholarshipRecord $record)
    {
        if ($record->approval_status !== 'conditional') {
            return false;
        }

        // Mark as notified to prevent duplicate reminders
        $record->update([
            'conditional_deadline_notified_at' => now()
        ]);

        // Here you would implement actual notification logic
        // For example: email, SMS, in-app notification
        Log::info('Deadline reminder sent', [
            'record_id' => $record->id,
            'deadline' => $record->conditional_deadline,
            'applicant_id' => $record->profile_id
        ]);

        return true;
    }

    /**
     * Check and process expired conditional approvals
     */
    public function processExpiredConditionalApprovals()
    {
        $expiredRecords = ScholarshipRecord::where('approval_status', 'conditional')
            ->where('conditional_deadline', '<', now())
            ->where('conditional_deadline_expired', false)
            ->with('profile')
            ->get();

        $processed = 0;
        $systemUser = $this->getSystemUser();

        foreach ($expiredRecords as $record) {
            try {
                $this->expireConditionalApproval($record, $systemUser);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to expire conditional approval', [
                    'record_id' => $record->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Processed expired conditional approvals', [
            'total_processed' => $processed,
            'total_found' => $expiredRecords->count()
        ]);

        return $processed;
    }

    /**
     * Send deadline reminders (typically run daily)
     */
    public function sendUpcomingDeadlineReminders($daysBefore = 3)
    {
        $reminderDate = now()->addDays($daysBefore);

        $records = ScholarshipRecord::where('approval_status', 'conditional')
            ->whereDate('conditional_deadline', $reminderDate->toDateString())
            ->whereNull('conditional_deadline_notified_at')
            ->with('profile')
            ->get();

        $sent = 0;

        foreach ($records as $record) {
            try {
                if ($this->sendDeadlineReminder($record)) {
                    $sent++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to send deadline reminder', [
                    'record_id' => $record->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Sent deadline reminders', [
            'total_sent' => $sent,
            'days_before' => $daysBefore
        ]);

        return $sent;
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

    public function resubmit(ScholarshipRecord $record, array $data = [])
    {
        if (!$record->isDeclined()) {
            throw new \InvalidArgumentException('Only declined applications can be resubmitted');
        }

        DB::transaction(function () use ($record, $data) {
            $oldStatus = $record->approval_status;

            $record->update([
                'approval_status' => 'resubmitted',
                'application_status' => 0, // Back to waiting
                'resubmitted_at' => now(),
                'resubmission_notes' => $data['notes'] ?? null,
                'resubmission_count' => $record->resubmission_count + 1,
                // Keep decline history but clear current decline status
                'declined_by' => null,
                'declined_at' => null,
            ]);

            $this->createStatusHistory($record, 'resubmitted', $oldStatus, null, $data['notes'] ?? null);

            // Auto-approve if configured
            if ($record->shouldAutoApprove()) {
                $this->autoApprove($record);
            }
        });
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
            throw new \InvalidArgumentException("Application with status '{$record->approval_status}' cannot be modified");
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
            'new_status' => $record->approval_status,
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
            'pending' => $query->clone()->where('approval_status', 'pending')->count(),
            'approved' => $query->clone()->where('approval_status', 'approved')->count(),
            'declined' => $query->clone()->where('approval_status', 'declined')->count(),
            'conditional' => $query->clone()->where('approval_status', 'conditional')->count(),
            'resubmitted' => $query->clone()->where('approval_status', 'resubmitted')->count(),
        ];
    }
}
