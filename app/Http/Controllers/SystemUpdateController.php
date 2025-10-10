<?php

namespace App\Http\Controllers;

use App\Models\SystemUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SystemUpdateController extends Controller
{
    /**
     * Get unread updates count for current user
     */
    public function getUnreadCount(): JsonResponse
    {
        $user = Auth::user();

        $unreadCount = SystemUpdate::active()
            ->forUser($user)
            ->whereDoesntHave('readByUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }

    /**
     * Get all updates for current user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $updates = SystemUpdate::active()
            ->forUser($user)
            ->with(['creator', 'readByUsers' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderByRaw("CASE 
                WHEN priority = 'urgent' THEN 1 
                WHEN priority = 'high' THEN 2 
                WHEN priority = 'normal' THEN 3 
                ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($update) use ($user) {
                return [
                    'id' => $update->id,
                    'title' => $update->title,
                    'content' => $update->content,
                    'type' => $update->type,
                    'priority' => $update->priority,
                    'created_at' => $update->created_at->format('M j, Y g:i A'),
                    'created_by' => $update->creator?->name ?? 'System',
                    'is_read' => $update->isReadBy($user),
                ];
            });

        return response()->json(['updates' => $updates]);
    }

    /**
     * Mark an update as read
     */
    public function markAsRead(SystemUpdate $systemUpdate): JsonResponse
    {
        $user = Auth::user();

        // Check if user can see this update
        $canSee = SystemUpdate::active()
            ->forUser($user)
            ->where('id', $systemUpdate->id)
            ->exists();

        if (!$canSee) {
            return response()->json(['message' => 'Update not found'], 404);
        }

        // Mark as read if not already read
        if (!$systemUpdate->isReadBy($user)) {
            $systemUpdate->readByUsers()->attach($user->id, ['read_at' => now()]);
        }

        return response()->json(['message' => 'Update marked as read']);
    }

    /**
     * Mark all updates as read for current user
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        $unreadUpdates = SystemUpdate::active()
            ->forUser($user)
            ->whereDoesntHave('readByUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadUpdates as $update) {
            $update->readByUsers()->attach($user->id, ['read_at' => now()]);
        }

        return response()->json(['message' => 'All updates marked as read']);
    }

    /**
     * Admin methods for managing updates
     */
    public function adminIndex(): JsonResponse
    {
        $updates = SystemUpdate::with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($update) {
                return [
                    'id' => $update->id,
                    'title' => $update->title,
                    'content' => $update->content,
                    'type' => $update->type,
                    'priority' => $update->priority,
                    'is_global' => $update->is_global,
                    'is_active' => $update->is_active,
                    'created_at' => $update->created_at->format('M j, Y g:i A'),
                    'created_by_name' => $update->creator?->name ?? 'System',
                    'expires_at' => $update->expires_at?->format('M j, Y g:i A'),
                ];
            });

        return response()->json(['updates' => $updates]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'in:info,warning,success,error',
            'priority' => 'in:low,normal,high,urgent',
            'is_global' => 'boolean',
            'target_roles' => 'array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $update = SystemUpdate::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type ?? 'info',
            'priority' => $request->priority ?? 'normal',
            'is_global' => $request->is_global ?? true,
            'target_roles' => $request->target_roles,
            'expires_at' => $request->expires_at,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'System update created successfully', 'update' => $update]);
    }

    public function destroy(SystemUpdate $systemUpdate): JsonResponse
    {
        $systemUpdate->update(['is_active' => false]);

        return response()->json(['message' => 'System update deactivated']);
    }
}
