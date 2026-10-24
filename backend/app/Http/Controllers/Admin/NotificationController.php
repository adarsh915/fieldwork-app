<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display the full notification center / activity feed.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->notifications();

        // Filter by Status (unread vs all)
        $status = $request->input('status', 'all');
        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        // Filter by Category
        $category = $request->input('category');
        if ($category && $category !== 'all') {
            $query->where(function ($q) use ($category) {
                $q->where('data->category', $category)
                  ->orWhere('data->type', 'like', "%{$category}%");
            });
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Filter by Search Query
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('data->message', 'like', "%{$search}%")
                  ->orWhere('data->title', 'like', "%{$search}%")
                  ->orWhere('data->user_name', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest()->paginate(25)->withQueryString();

        // Calculate Summary Stats
        $today = Carbon::today()->toDateString();
        $stats = [
            'unread' => $user->unreadNotifications()->count(),
            'today'  => $user->notifications()->whereDate('created_at', $today)->count(),
            'visits' => $user->notifications()->whereDate('created_at', $today)->where('data->category', 'visit')->count(),
            'orders' => $user->notifications()->whereDate('created_at', $today)->where('data->category', 'order')->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Live JSON feed for the topbar notification bell polling.
     */
    public function unreadFeed(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = $user->unreadNotifications()->count();
        $latest = $user->unreadNotifications()->latest()->take(10)->get()->map(function ($n) {
            $data = $n->data;
            $url = $data['url'] ?? null;
            if (!$url && !empty($data['day_start_id'])) {
                $anchor = match ($data['category'] ?? '') {
                    'visit'   => '#visits',
                    'expense' => '#expenses',
                    'order'   => '#orders',
                    default   => '',
                };
                $url = route('admin.day-starts.show', $data['day_start_id']) . $anchor;
            }

            return [
                'id'         => $n->id,
                'title'      => $data['title'] ?? 'Activity Notification',
                'message'    => $data['message'] ?? 'New activity recorded',
                'category'   => $data['category'] ?? 'general',
                'icon'       => $data['icon'] ?? 'bell',
                'url'        => $url,
                'read_url'   => route('admin.notifications.read', $n->id),
                'is_updated' => !empty($data['is_updated']),
                'created_at' => $n->created_at->diffForHumans(null, false, true),
            ];
        });

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'notifications'=> $latest,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->each->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notification deleted.');
    }

    /**
     * Delete all read notifications for current user.
     */
    public function clearRead(Request $request)
    {
        $request->user()->readNotifications()->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Read notifications cleared.');
    }
}
