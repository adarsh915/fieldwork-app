<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user with unread count.
     * GET /api/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = UserNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'data'         => $notifications,
        ]);
    }

    /**
     * Get unread notifications for the authenticated user.
     * GET /api/notifications/latest
     */
    public function latest(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $unreadCount = UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'data'         => $notifications,
        ]);
    }

    /**
     * Get unread notification count.
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }

    /**
     * Mark a notification as read.
     * POST /api/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = UserNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($notification) {
            $notification->update(['is_read' => true]);
        }

        $remainingUnread = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $remainingUnread,
            'message'      => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read.
     * POST /api/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success'      => true,
            'unread_count' => 0,
            'message'      => 'All notifications marked as read.',
        ]);
    }
}
