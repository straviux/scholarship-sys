<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get all activities for a profile
     */
    public function profileActivities(Request $request, $profileId)
    {
        $activities = ActivityLog::where('profile_id', $profileId)
            ->with(['user' => function ($q) {
                $q->with('roles');
            }])
            ->orderBy('performed_at', 'desc')
            ->paginate(50);

        return response()->json($activities);
    }

    /**
     * Get approval history timeline - only status changes for scholarship records
     */
    public function approvalHistory(Request $request, $recordId)
    {
        $statusUpdates = ActivityLog::where('activity_type', 'status_change')
            ->where('details->record_id', $recordId)
            ->orWhere(function ($q) use ($recordId) {
                // Also check if there's a scholarship record with this ID
                $q->where('activity_type', 'status_change')
                    ->whereHas('profile', function ($subQuery) use ($recordId) {
                        // You may need to adjust this based on your relationship
                    });
            })
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'username');
            }])
            ->orderBy('performed_at', 'desc')
            ->get();

        return response()->json($statusUpdates);
    }

    /**
     * Get approval status timeline for a profile - all unified status changes
     */
    public function statusTimeline(Request $request, $profileId)
    {
        $timeline = ActivityLog::where('profile_id', $profileId)
            ->where('activity_type', 'status_changed')
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'username');
            }])
            ->orderBy('performed_at', 'asc')
            ->get()
            ->map(function ($log) {
                $details = is_array($log->details) ? $log->details : json_decode($log->details, true);
                if (is_array($details) && isset($details['activity_type'])) {
                    $details['activity_type'] = 'Status Change';
                }

                return [
                    'id' => $log->id,
                    'status' => $log->action,
                    'old_status' => $log->old_value,
                    'new_status' => $log->new_value,
                    'changed_by' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'username' => $log->user->username
                    ] : null,
                    'remarks' => $log->remarks,
                    'description' => $log->description,
                    'activity_type' => 'Status Changed',
                    'performed_at' => $log->performed_at,
                    'details' => $details
                ];
            });

        return response()->json($timeline);
    }
}
