<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    /**
     * Display push notification composer and device token statuses.
     */
    public function index()
    {
        $users = User::with('roles')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tokenCount = $users->whereNotNull('fcm_token')->count();

        return view('admin.notifications.send_push', compact('users', 'tokenCount'));
    }

    /**
     * Send push notification to selected user or group.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient' => 'required|string',
            'title'     => 'required|string|max:150',
            'body'      => 'required|string|max:500',
            'screen'    => 'nullable|string|max:100',
        ]);

        $title = $validated['title'];
        $body  = $validated['body'];
        $data  = [
            'screen'    => $validated['screen'] ?? 'Home',
            'timestamp' => now()->toIso8601String(),
        ];

        if ($validated['recipient'] === 'all') {
            $recipients = User::where('is_active', true)->get();
            if ($recipients->isEmpty()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'No active users found.'], 422);
                }
                return back()->with('error', 'No active users found.');
            }

            foreach ($recipients as $u) {
                \App\Models\UserNotification::create([
                    'user_id' => $u->id,
                    'title'   => $title,
                    'body'    => $body,
                    'screen'  => $data['screen'] ?? 'Home',
                ]);
            }

            $tokenRecipients = $recipients->whereNotNull('fcm_token');
            if ($tokenRecipients->isNotEmpty()) {
                $pushResult = FirebasePushService::sendToUsers($tokenRecipients, $title, $body, $data);
                if (!$pushResult['success'] && !empty($pushResult['errors'])) {
                    $errMsg = "Notification saved to DB, but push delivery failed: " . implode('; ', $pushResult['errors']);
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $errMsg], 422);
                    }
                    return back()->with('error', $errMsg);
                }
            }

            $successMsg = "Notification sent to {$recipients->count()} user(s)!";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }
            return back()->with('status', $successMsg);
        }

        if ($validated['recipient'] === 'field_workers') {
            $recipients = User::where('is_active', true)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']))
                ->get();

            if ($recipients->isEmpty()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'No field workers found.'], 422);
                }
                return back()->with('error', 'No field workers found.');
            }

            foreach ($recipients as $u) {
                \App\Models\UserNotification::create([
                    'user_id' => $u->id,
                    'title'   => $title,
                    'body'    => $body,
                    'screen'  => $data['screen'] ?? 'Home',
                ]);
            }

            $tokenRecipients = $recipients->whereNotNull('fcm_token');
            if ($tokenRecipients->isNotEmpty()) {
                $pushResult = FirebasePushService::sendToUsers($tokenRecipients, $title, $body, $data);
                if (!$pushResult['success'] && !empty($pushResult['errors'])) {
                    $errMsg = "Notification saved to DB, but push delivery failed: " . implode('; ', $pushResult['errors']);
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $errMsg], 422);
                    }
                    return back()->with('error', $errMsg);
                }
            }

            $successMsg = "Notification sent to {$recipients->count()} field worker(s)!";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }
            return back()->with('status', $successMsg);
        }

        // Specific single user
        $user = User::findOrFail((int)$validated['recipient']);

        // Save to DB
        $notificationRecord = \App\Models\UserNotification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'screen'  => $data['screen'] ?? 'Home',
        ]);

        if (!empty($user->fcm_token)) {
            $pushResult = FirebasePushService::sendToUser($user, $title, $body, $data);
            if (!$pushResult['success']) {
                $errMsg = "Notification saved to DB, but push delivery failed: " . ($pushResult['message'] ?? 'Unknown error');
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errMsg], 422);
                }
                return back()->with('error', $errMsg);
            }
        } else {
            $warnMsg = "Notification saved in database, but {$user->name} has no device registered (fcm_token is empty).";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'warning' => true, 'message' => $warnMsg]);
            }
            return back()->with('error', $warnMsg);
        }

        $successMsg = "Notification dispatched successfully to {$user->name}!";
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }
        return back()->with('status', $successMsg);
    }
}
