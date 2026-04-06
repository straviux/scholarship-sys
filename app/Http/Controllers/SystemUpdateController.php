<?php

namespace App\Http\Controllers;

use App\Models\SystemUpdate;
use App\Services\ActivityLogService;
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

        return response()->json(['unread_count' => $unreadCount])
            ->header('Cache-Control', 'private, max-age=60');
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
                    'markdown_content' => $update->markdown_content,
                    'is_markdown' => $update->is_markdown,
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
                    'markdown_content' => $update->markdown_content,
                    'is_markdown' => $update->is_markdown,
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
            'content' => 'nullable|string',
            'markdown_content' => 'nullable|string',
            'is_markdown' => 'boolean',
            'type' => 'in:info,warning,success,error',
            'priority' => 'in:low,normal,high,urgent',
            'is_global' => 'boolean',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Ensure at least one content field is provided
        if (!$request->content && !$request->markdown_content) {
            return response()->json([
                'message' => 'Either content or markdown_content must be provided'
            ], 422);
        }

        try {
            $update = SystemUpdate::create([
                'title' => $request->title,
                'content' => $request->content ?? '',
                'markdown_content' => $request->markdown_content ?? '',
                'is_markdown' => $request->is_markdown ?? false,
                'type' => $request->type ?? 'info',
                'priority' => $request->priority ?? 'normal',
                'is_global' => $request->is_global ?? true,
                'target_roles' => $request->target_roles ?? [],
                'expires_at' => $request->expires_at,
                'created_by' => Auth::id(),
            ]);

            return response()->json(['message' => 'System update created successfully', 'update' => $update]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create system update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a system update (soft delete)
     */
    public function deactivate(SystemUpdate $systemUpdate): JsonResponse
    {
        try {
            $systemUpdate->update(['is_active' => false]);
            return response()->json(['message' => 'System update deactivated']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate system update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate a system update
     */
    public function reactivate(SystemUpdate $systemUpdate): JsonResponse
    {
        try {
            $systemUpdate->update(['is_active' => true]);
            return response()->json(['message' => 'System update reactivated']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reactivate system update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a system update
     */
    public function destroy(SystemUpdate $systemUpdate): JsonResponse
    {
        try {
            $systemUpdate->forceDelete();
            return response()->json(['message' => 'System update permanently deleted']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete system update',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
