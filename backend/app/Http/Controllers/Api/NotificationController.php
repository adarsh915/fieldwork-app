<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $notifications,
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

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }
}
