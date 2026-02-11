<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceAnnouncement;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Get current maintenance status (public endpoint for alerts/modals)
     */
    public function getPublicStatus()
    {
        $maintenance = MaintenanceAnnouncement::getActive();

        if (!$maintenance) {
            return response()->json([
                'is_under_maintenance' => false,
                'announcement' => null,
            ]);
        }

        return response()->json([
            'is_under_maintenance' => MaintenanceAnnouncement::isUnderMaintenance(),
            'announcement' => [
                'title' => $maintenance->title,
                'message' => $maintenance->message,
                'type' => $maintenance->type,
                'countdown' => $maintenance->getCountdownData(),
            ],
        ]);
    }

    /**
     * Get current maintenance status
     */
    public function getStatus()
    {
        $maintenance = MaintenanceAnnouncement::getActive();

        if (!$maintenance) {
            return response()->json([
                'is_under_maintenance' => false,
                'announcement' => null,
            ]);
        }

        return response()->json([
            'is_under_maintenance' => MaintenanceAnnouncement::isUnderMaintenance(),
            'announcement' => [
                'title' => $maintenance->title,
                'message' => $maintenance->message,
                'type' => $maintenance->type,
                'countdown' => $maintenance->getCountdownData(),
            ],
        ]);
    }

    /**
     * Create or update maintenance announcement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'type' => 'required|in:info,warning,critical',
            'allow_admin_access' => 'required|boolean',
        ]);

        $maintenance = MaintenanceAnnouncement::first() ?? new MaintenanceAnnouncement();
        $maintenance->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance announcement updated',
            'data' => $maintenance,
        ]);
    }

    /**
     * Activate maintenance
     */
    public function activate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'type' => 'required|in:info,warning,critical',
        ]);

        // Deactivate any existing announcements
        MaintenanceAnnouncement::query()->update(['is_active' => false]);

        $maintenance = MaintenanceAnnouncement::create([
            ...$validated,
            'is_active' => true,
            'allow_admin_access' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance activated',
            'data' => $maintenance,
        ]);
    }

    /**
     * Deactivate maintenance
     */
    public function deactivate()
    {
        MaintenanceAnnouncement::query()->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance deactivated',
        ]);
    }

    /**
     * Get all maintenance records
     */
    public function list()
    {
        $announcements = MaintenanceAnnouncement::latest()->get();

        return response()->json([
            'data' => $announcements,
        ]);
    }
}
