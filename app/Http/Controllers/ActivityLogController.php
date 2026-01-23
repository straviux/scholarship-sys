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
}
