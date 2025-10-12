<?php

namespace App\Services;

use App\Models\ScholarshipRecord;
use App\Models\ScholarshipCompletion;
use App\Models\ScholarshipApprovalHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScholarshipCompletionService
{
    public function markAsCompleted(ScholarshipRecord $record, array $data, User $verifier)
    {
        $this->validateCompletionData($data);
        $this->validateCanComplete($record);

        DB::transaction(function () use ($record, $data, $verifier) {
            // Update the scholarship record
            $record->update([
                'completion_status' => 'completed',
                'completion_date' => $data['completion_date'],
                'completion_remarks' => $data['completion_remarks'] ?? null,
            ]);

            // Create completion record
            ScholarshipCompletion::create([
                'scholarship_record_id' => $record->id,
                'profile_id' => $record->profile_id,
                'program_id' => $record->program_id,
                'course_id' => $record->course_id,
                'school_id' => $record->school_id,
                'completion_date' => $data['completion_date'],
                'final_grade' => $data['final_grade'] ?? null,
                'graduation_date' => $data['graduation_date'] ?? null,
                'honors' => $data['honors'] ?? null,
                'completion_certificate_path' => $data['certificate_path'] ?? null,
                'completion_remarks' => $data['completion_remarks'] ?? null,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

            // Create status history
            ScholarshipApprovalHistory::create([
                'scholarship_record_id' => $record->id,
                'action' => 'completed',
                'previous_status' => $record->getOriginal('completion_status'),
                'new_status' => 'completed',
                'performed_by' => $verifier->id,
                'remarks' => $data['completion_remarks'] ?? null,
                'performed_at' => now(),
            ]);

            Log::info('Scholarship marked as completed', [
                'record_id' => $record->id,
                'verified_by' => $verifier->id,
                'completion_date' => $data['completion_date']
            ]);
        });
    }

    public function discontinue(ScholarshipRecord $record, array $data, User $user)
    {
        $this->validateCanDiscontinue($record);

        $record->update([
            'completion_status' => 'discontinued',
            'completion_date' => $data['discontinue_date'] ?? now(),
            'completion_remarks' => $data['reason'],
        ]);

        // Create history record
        ScholarshipApprovalHistory::create([
            'scholarship_record_id' => $record->id,
            'action' => 'discontinued',
            'previous_status' => $record->getOriginal('completion_status'),
            'new_status' => 'discontinued',
            'performed_by' => $user->id,
            'remarks' => $data['reason'],
            'performed_at' => now(),
        ]);
    }

    private function validateCompletionData(array $data)
    {
        $validStatuses = array_keys(config('scholarship.completion_statuses'));

        if (isset($data['completion_status']) && !in_array($data['completion_status'], $validStatuses)) {
            throw new \InvalidArgumentException("Invalid completion status: {$data['completion_status']}");
        }
    }

    private function validateCanComplete(ScholarshipRecord $record)
    {
        if (!$record->isActive()) {
            throw new \InvalidArgumentException("Only active scholarships can be marked as completed");
        }
    }

    private function validateCanDiscontinue(ScholarshipRecord $record)
    {
        if (!$record->isActive()) {
            throw new \InvalidArgumentException("Only active scholarships can be discontinued");
        }
    }
}
