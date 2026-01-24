<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserActivityLogController extends Controller
{
    /**
     * Get activity logs for the authenticated user (recent activities preview)
     */
    public function recentActivities(Request $request)
    {
        $limit = $request->query('limit', 10);

        $activities = ActivityLog::where('user_id', Auth::id())
            ->with('profile')
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $profileName = null;
                if ($log->profile) {
                    $profileName = trim($log->profile->first_name . ' ' . ($log->profile->middle_name ?? '') . ' ' . $log->profile->last_name);
                }

                return [
                    'id' => $log->id,
                    'activity_type' => $this->getActivityTypeLabel($log->activity_type),
                    'action' => $log->action,
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                    'remarks' => $log->remarks,
                    'description' => $log->description,
                    'performed_at' => $log->performed_at,
                    'profile_id' => $log->profile_id,
                    'profile_name' => $profileName,
                    'details' => $log->details,
                    'snapshot_before' => $log->snapshot_before,
                    'snapshot_after' => $log->snapshot_after
                ];
            });

        return response()->json([
            'data' => $activities,
            'total' => ActivityLog::where('user_id', Auth::id())->count()
        ]);
    }

    /**
     * Get all activity logs for the authenticated user (paginated)
     */
    public function userActivityLogs(Request $request)
    {
        $perPage = $request->query('per_page', 20);

        $activities = ActivityLog::where('user_id', Auth::id())
            ->with('profile')
            ->orderBy('performed_at', 'desc')
            ->paginate($perPage)
            ->through(function ($log) {
                $profileName = null;
                if ($log->profile) {
                    $profileName = trim($log->profile->first_name . ' ' . ($log->profile->middle_name ?? '') . ' ' . $log->profile->last_name);
                }

                return [
                    'id' => $log->id,
                    'activity_type' => $this->getActivityTypeLabel($log->activity_type),
                    'action' => $log->action,
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                    'remarks' => $log->remarks,
                    'description' => $log->description,
                    'performed_at' => $log->performed_at,
                    'profile_id' => $log->profile_id,
                    'profile_name' => $profileName,
                    'details' => $log->details,
                    'snapshot_before' => $log->snapshot_before,
                    'snapshot_after' => $log->snapshot_after
                ];
            });

        return response()->json($activities);
    }

    /**
     * Get activity type label for display
     */
    private function getActivityTypeLabel($type)
    {
        $labels = [
            'profile_edited' => 'Profile Updated',
            'profile_updated' => 'Profile Updated',
            'attachment_uploaded' => 'Attachment Uploaded',
            'record_created' => 'Record Created',
            'record_updated' => 'Record Updated',
            'record_deleted' => 'Record Deleted',
            'status_changed' => 'Status Changed',
            'profile_created' => 'Profile Created'
        ];
        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
