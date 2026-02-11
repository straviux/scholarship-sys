<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display user activity log
     */
    public function index(Request $request)
    {
        $query = UserActivity::where('user_id', auth()->id())
            ->latest();

        // Filter by action if provided
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $activities = $query->paginate(15);

        // Get available actions for filter dropdown
        $availableActions = UserActivity::where('user_id', auth()->id())
            ->distinct()
            ->pluck('action')
            ->toArray();

        return Inertia::render('User/Activity', [
            'activities' => $activities,
            'availableActions' => $availableActions,
        ]);
    }
}
