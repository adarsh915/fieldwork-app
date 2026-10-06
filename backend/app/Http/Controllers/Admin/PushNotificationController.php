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
                    return back()->with('error', "Notification saved to DB, but push delivery failed: " . implode('; ', $pushResult['errors']));
                }
            }

            return back()->with('status', "Notification sent to {$recipients->count()} user(s)!");
        }

        if ($validated['recipient'] === 'field_workers') {
            $recipients = User::where('is_active', true)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['Field Worker', 'field_worker', 'Employee']))
                ->get();

            if ($recipients->isEmpty()) {
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
                    return back()->with('error', "Notification saved to DB, but push delivery failed: " . implode('; ', $pushResult['errors']));
                }
            }

            return back()->with('status', "Notification sent to {$recipients->count()} field worker(s)!");
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
                return back()->with('error', "Notification saved to DB, but push delivery failed: " . ($pushResult['message'] ?? 'Unknown error'));
            }
        } else {
            return back()->with('error', "Notification saved in database, but {$user->name} has no device registered (fcm_token is empty).");
        }

        return back()->with('status', "Notification dispatched successfully to {$user->name}!");
    }
}
