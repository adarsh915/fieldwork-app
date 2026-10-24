<?php

namespace App\Services;

use App\Models\DayStart;
use App\Models\ExpenseBill;
use App\Models\Leave;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use App\Notifications\UserActivityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ActivityNotificationService
{
    /**
     * Get all recipients for an activity performed by a user.
     * Includes all active Admins/Super Admins and the user's reporting manager.
     */
    /**
     * Fetch full management chain up the reporting hierarchy (Manager -> VP -> MD etc.)
     */
    public static function getManagementChain(?User $user): Collection
    {
        $managers = collect();
        $current = $user;
        $visited = [];
        while ($current && $current->reporting_to && !in_array($current->reporting_to, $visited)) {
            $visited[] = $current->reporting_to;
            $m = User::where('id', $current->reporting_to)->where('is_active', true)->first();
            if ($m) {
                $managers->push($m);
                $current = $m;
            } else {
                break;
            }
        }
        return $managers;
    }

    /**
     * Get all recipients for an activity performed by a user.
     * Includes all active Admins/Super Admins and the user's reporting manager.
     */
    public static function getRecipientsForUser(?User $actor): Collection
    {
        try {
            // 1. Fetch active Admins, Super Admins, and HR
            $admins = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'Admin', 'admin', 'super_admin', 'HR', 'hr']);
            })->where('is_active', true)->get();

            // 2. Fetch full management chain up the reporting hierarchy (Manager -> VP -> MD etc.)
            $managers = self::getManagementChain($actor);

            // 3. Merge and deduplicate by user ID
            return $admins->merge($managers)->unique('id');
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to resolve recipients: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Notify Admins & Manager when a user starts their day.
     */
    public static function notifyDayStarted(DayStart $dayStart): void
    {
        try {
            $dayStart->loadMissing(['user', 'tourPurpose']);
            $user = $dayStart->user;
            $userName = $user?->name ?? 'Employee';
            $purpose = $dayStart->tourPurpose?->name ?? 'Daily Tour';
            $openingKm = number_format((float) ($dayStart->opening_km ?? 0), 0);

            $payload = [
                'title'        => 'Day Started',
                'message'      => "{$userName} started their day ({$purpose}) with Opening KM: {$openingKm}",
                'category'     => 'day_start',
                'icon'         => 'play-circle',
                'url'          => route('admin.day-starts.show', $dayStart->id),
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'day_start_id' => $dayStart->id,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify DayStarted: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a user ends their day.
     */
    public static function notifyDayEnded(DayStart $dayStart): void
    {
        try {
            $dayStart->loadMissing(['user']);
            $user = $dayStart->user;
            $userName = $user?->name ?? 'Employee';
            $closingKm = number_format((float) ($dayStart->closing_km ?? 0), 0);
            $totalKm = ($dayStart->closing_km && $dayStart->opening_km) 
                ? number_format((float) ($dayStart->closing_km - $dayStart->opening_km), 1) 
                : null;

            $kmText = $totalKm ? " (Total {$totalKm} KM traveled)" : '';

            $payload = [
                'title'        => 'Day Ended',
                'message'      => "{$userName} ended their day with Closing KM: {$closingKm}{$kmText}",
                'category'     => 'day_start',
                'icon'         => 'stop-circle',
                'url'          => route('admin.day-starts.show', $dayStart->id),
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'day_start_id' => $dayStart->id,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify DayEnded: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a user checks in at a party.
     */
    public static function notifyVisitCheckIn(Visit $visit): void
    {
        try {
            $visit->loadMissing(['user', 'party']);
            $user = $visit->user;
            $userName = $user?->name ?? 'Field Worker';
            $partyName = $visit->party?->name ?? 'Party';
            $purpose = $visit->purpose ? " — {$visit->purpose}" : '';
            $hasPhoto = !empty($visit->check_in_photo) ? ' (Photo attached)' : '';

            $url = $visit->day_start_id 
                ? route('admin.day-starts.show', $visit->day_start_id) . '#visits'
                : route('admin.visits.index');

            $payload = [
                'title'        => 'Party Check-In',
                'message'      => "{$userName} checked in at {$partyName}{$purpose}{$hasPhoto}",
                'category'     => 'visit',
                'icon'         => 'map-pin',
                'url'          => $url,
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'day_start_id' => $visit->day_start_id,
                'visit_id'     => $visit->id,
                'party_id'     => $visit->party_id,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify VisitCheckIn: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a user checks out from a party.
     */
    public static function notifyVisitCheckOut(Visit $visit): void
    {
        try {
            $visit->loadMissing(['user', 'party']);
            $user = $visit->user;
            $userName = $user?->name ?? 'Field Worker';
            $partyName = $visit->party?->name ?? 'Party';
            $notesPreview = !empty($visit->notes) ? ' | Notes: ' . substr($visit->notes, 0, 50) : '';

            $url = $visit->day_start_id 
                ? route('admin.day-starts.show', $visit->day_start_id) . '#visits'
                : route('admin.visits.index');

            $payload = [
                'title'        => 'Party Check-Out',
                'message'      => "{$userName} completed visit at {$partyName}{$notesPreview}",
                'category'     => 'visit',
                'icon'         => 'check-circle-2',
                'url'          => $url,
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'day_start_id' => $visit->day_start_id,
                'visit_id'     => $visit->id,
                'party_id'     => $visit->party_id,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify VisitCheckOut: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when consultation notes for a visit are updated.
     * Implements in-place sync for unread visit alerts + smart dispatch if already read.
     */
    public static function notifyVisitNotesUpdated(Visit $visit, ?string $oldNotes = null, ?User $editor = null): void
    {
        try {
            $visit->loadMissing(['user', 'party']);
            $user = $visit->user;
            $userName = $user?->name ?? 'Field Worker';
            $partyName = $visit->party?->name ?? 'Party';
            $notesPreview = !empty($visit->notes) ? ' | Notes: ' . substr($visit->notes, 0, 50) : '';

            $url = $visit->day_start_id 
                ? route('admin.day-starts.show', $visit->day_start_id) . '#visits'
                : route('admin.visits.index');

            $payload = [
                'title'        => 'Party Visit Notes Updated',
                'message'      => "{$userName} updated notes for {$partyName}{$notesPreview}",
                'category'     => 'visit',
                'icon'         => 'check-circle-2',
                'url'          => $url,
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'day_start_id' => $visit->day_start_id,
                'visit_id'     => $visit->id,
                'party_id'     => $visit->party_id,
                'is_updated'   => true,
                'created_at'   => now()->toISOString(),
            ];

            $recipients = self::getRecipientsForUser($user);

            foreach ($recipients as $recipient) {
                $existingUnread = $recipient->unreadNotifications()
                    ->where('data->visit_id', $visit->id)
                    ->first();

                if ($existingUnread) {
                    $existingUnread->data = $payload;
                    $existingUnread->created_at = now();
                    $existingUnread->save();
                } else {
                    $recipient->notify(new UserActivityNotification($payload));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify VisitNotesUpdated: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a new order is placed.
     */
    public static function notifyOrderPlaced(Order $order): void
    {
        try {
            $order->loadMissing(['user', 'party']);
            $user = $order->user;
            $userName = $user?->name ?? 'Field Worker';
            $partyName = $order->party?->name ?? 'Dealer';
            $amount = number_format((float) $order->total_amount, 2);

            $payload = [
                'title'        => 'New Order #' . ($order->order_no ?? $order->id),
                'message'      => "{$userName} booked an order of ₹{$amount} for {$partyName}",
                'category'     => 'order',
                'icon'         => 'shopping-bag',
                'url'          => route('admin.orders.show', $order->id),
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'order_id'     => $order->id,
                'party_id'     => $order->party_id,
                'amount'       => $order->total_amount,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify OrderPlaced: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when an expense claim is submitted.
     */
    public static function notifyExpenseSubmitted(ExpenseBill $expense): void
    {
        try {
            $expense->loadMissing(['user', 'category']);
            $user = $expense->user;
            $userName = $user?->name ?? 'Employee';
            $categoryName = $expense->category?->name ?? 'Expense';
            $amount = number_format((float) $expense->amount, 2);

            $url = $expense->day_start_id
                ? route('admin.day-starts.show', $expense->day_start_id) . '#expenses'
                : route('admin.expenses.index');

            $payload = [
                'title'        => 'Expense Claim Submitted',
                'message'      => "{$userName} submitted {$categoryName} expense of ₹{$amount}",
                'category'     => 'expense',
                'icon'         => 'receipt',
                'url'          => $url,
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'expense_id'   => $expense->id,
                'day_start_id' => $expense->day_start_id,
                'amount'       => $expense->amount,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify ExpenseSubmitted: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when an expense claim is updated / edited.
     * Implements in-place sync for unread alerts + smart dispatch if already read.
     */
    public static function notifyExpenseUpdated(ExpenseBill $expense, ?float $oldAmount = null, ?User $editor = null): void
    {
        try {
            $expense->loadMissing(['user', 'category']);
            $user = $expense->user;
            $userName = $user?->name ?? 'Employee';
            $categoryName = $expense->category?->name ?? 'Expense';
            $newAmount = number_format((float) $expense->amount, 2);

            $diffText = '';
            if ($oldAmount !== null && round((float) $oldAmount, 2) !== round((float) $expense->amount, 2)) {
                $oldAmountFormatted = number_format((float) $oldAmount, 2);
                $diffText = " (previously ₹{$oldAmountFormatted})";
            }

            $editorText = ($editor && $user && $editor->id !== $user->id) 
                ? " [edited by {$editor->name}]" 
                : '';

            $url = $expense->day_start_id
                ? route('admin.day-starts.show', $expense->day_start_id) . '#expenses'
                : route('admin.expenses.index');

            $payload = [
                'title'        => 'Expense Claim Updated',
                'message'      => "{$userName}{$editorText} updated {$categoryName} expense to ₹{$newAmount}{$diffText}",
                'category'     => 'expense',
                'icon'         => 'receipt',
                'url'          => $url,
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'expense_id'   => $expense->id,
                'day_start_id' => $expense->day_start_id,
                'amount'       => $expense->amount,
                'is_updated'   => true,
                'created_at'   => now()->toISOString(),
            ];

            $recipients = self::getRecipientsForUser($user);

            foreach ($recipients as $recipient) {
                // Check if recipient has an unread notification for this expense
                $existingUnread = $recipient->unreadNotifications()
                    ->where('data->expense_id', $expense->id)
                    ->first();

                if ($existingUnread) {
                    // In-place sync: update payload and bump timestamp to now() so it bubbles to top of unread list
                    $existingUnread->data = $payload;
                    $existingUnread->created_at = now();
                    $existingUnread->save();
                } else {
                    // If previously read or dismissed, send new notification alerting of the edit
                    $recipient->notify(new UserActivityNotification($payload));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify ExpenseUpdated: ' . $e->getMessage());
        }
    }

    /**
     * Get all active users who hold final expense approval authority or organization-wide view authority.
     * Evaluated strictly via permissions so any new role created in the future works automatically.
     */
    public static function getExpenseFinalApprovers(): Collection
    {
        try {
            return User::where('is_active', true)
                ->where(function ($q) {
                    $q->whereHas('permissions', fn($pq) => $pq->whereIn('name', ['expenses.final_approve', 'expenses.view-all', 'expenses.manage']))
                      ->orWhereHas('roles.permissions', fn($rpq) => $rpq->whereIn('name', ['expenses.final_approve', 'expenses.view-all', 'expenses.manage']))
                      ->orWhereHas('roles', fn($rq) => $rq->whereIn('name', ['Super Admin', 'super_admin', 'Admin', 'admin']));
                })
                ->get();
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to resolve final approvers: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Notify Approvers & Employee when an expense is Level 1 verified by their reporting manager.
     */
    public static function notifyExpenseTempApproved(ExpenseBill $expense): void
    {
        try {
            $expense->loadMissing(['user', 'category', 'tempApprovedBy']);
            $user = $expense->user;
            $userName = $user?->name ?? 'Employee';
            $managerName = $expense->tempApprovedBy?->name ?? 'Reporting Manager';
            $amount = number_format((float) $expense->amount, 2);

            // 1. Notify Employee in mobile app
            if ($user) {
                \App\Models\UserNotification::create([
                    'user_id' => $user->id,
                    'title'   => 'Expense Verified by Manager',
                    'body'    => "Your expense of ₹{$amount} was verified by {$managerName}. Forwarded for final approval.",
                    'screen'  => 'ExpenseList',
                    'is_read' => false,
                ]);

                // Web notification for submitter
                if ($expense->temp_approved_by !== $user->id) {
                    $user->notify(new UserActivityNotification([
                        'title'        => 'Expense Claim Verified',
                        'message'      => "Your expense of ₹{$amount} was verified by {$managerName}. Forwarded for final approval.",
                        'category'     => 'expense',
                        'icon'         => 'check-circle-2',
                        'url'          => route('admin.expenses.index'),
                        'user_id'      => $user->id,
                        'user_name'    => $userName,
                        'expense_id'   => $expense->id,
                        'day_start_id' => $expense->day_start_id,
                        'amount'       => $expense->amount,
                        'created_at'   => now()->toISOString(),
                    ]));
                }
            }

            // 2. Notify users with final approval authority (permission-driven)
            $approvers = self::getExpenseFinalApprovers()
                ->reject(fn($u) => $u->id === $expense->temp_approved_by || ($user && $u->id === $user->id));

            if ($approvers->isNotEmpty()) {
                $payload = [
                    'title'        => 'Expense Ready for Final Approval',
                    'message'      => "{$userName}'s expense of ₹{$amount} verified by {$managerName}. Awaiting final approval.",
                    'category'     => 'expense',
                    'icon'         => 'check-circle-2',
                    'url'          => route('admin.expenses.index'),
                    'user_id'      => $user?->id,
                    'user_name'    => $userName,
                    'expense_id'   => $expense->id,
                    'day_start_id' => $expense->day_start_id,
                    'amount'       => $expense->amount,
                    'created_at'   => now()->toISOString(),
                ];
                Notification::send($approvers, new UserActivityNotification($payload));
            }
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify ExpenseTempApproved: ' . $e->getMessage());
        }
    }

    /**
     * Notify Employee, Reporting Chain, and Approvers when an expense receives Level 2 Final Approval.
     */
    public static function notifyExpenseFinalApproved(ExpenseBill $expense): void
    {
        try {
            $expense->loadMissing(['user', 'category', 'approvedBy', 'tempApprovedBy']);
            $user = $expense->user;
            $userName = $user?->name ?? 'Employee';
            $amount = number_format((float) $expense->amount, 2);

            $approver = $expense->approvedBy;
            $approverName = $approver?->name ?? 'Management';
            $approverRole = $approver?->roles?->first()?->name 
                ?? ($approver?->can('expenses.final_approve') ? 'HR' : 'Management');
            $approverTag = "{$approverName} ({$approverRole})";
            $actionTitle = "Team Expense Approved by {$approverRole}";

            // 1. Notify Submitter (Both Mobile and Web Admin Bell)
            if ($user) {
                \App\Models\UserNotification::create([
                    'user_id' => $user->id,
                    'title'   => 'Expense Final Approved',
                    'body'    => "Your expense of ₹{$amount} was approved by {$approverName}. Sent for reimbursement payout.",
                    'screen'  => 'ExpenseList',
                    'is_read' => false,
                ]);

                if ($expense->approved_by !== $user->id) {
                    $user->notify(new UserActivityNotification([
                        'title'        => 'Expense Claim Approved',
                        'message'      => "Your expense of ₹{$amount} was approved by {$approverName}. Sent for reimbursement payout.",
                        'category'     => 'expense',
                        'icon'         => 'check-circle-2',
                        'url'          => route('admin.expenses.index'),
                        'user_id'      => $user->id,
                        'user_name'    => $userName,
                        'expense_id'   => $expense->id,
                        'day_start_id' => $expense->day_start_id,
                        'amount'       => $expense->amount,
                        'created_at'   => now()->toISOString(),
                    ]));
                }
            }

            // 2. Notify Management Chain (Reporting hierarchy up to the top)
            $managers = self::getManagementChain($user);

            // Also include tempApprovedBy manager if not already in chain
            if ($expense->temp_approved_by && !$managers->contains('id', $expense->temp_approved_by)) {
                $tempManager = User::where('id', $expense->temp_approved_by)->where('is_active', true)->first();
                if ($tempManager) {
                    $managers->push($tempManager);
                }
            }

            // Fetch users with expense approval / organization-wide view authority (HR, Finance, Admins)
            $approvers = self::getExpenseFinalApprovers();

            // Merge recipients, remove duplicates, and exclude the approver and the submitter
            $recipients = $managers->merge($approvers)
                ->unique('id')
                ->reject(fn($u) => $u->id === $expense->approved_by || ($user && $u->id === $user->id));

            foreach ($recipients as $recipient) {
                // A. Web Admin Notification
                $payload = [
                    'title'        => $actionTitle,
                    'message'      => "{$approverTag} approved ₹{$amount} expense for {$userName}.",
                    'category'     => 'expense',
                    'icon'         => 'check-check',
                    'url'          => route('admin.expenses.index'),
                    'user_id'      => $user?->id,
                    'user_name'    => $userName,
                    'expense_id'   => $expense->id,
                    'day_start_id' => $expense->day_start_id,
                    'amount'       => $expense->amount,
                    'created_at'   => now()->toISOString(),
                ];
                $recipient->notify(new UserActivityNotification($payload));

                // B. Mobile App Notification
                \App\Models\UserNotification::create([
                    'user_id' => $recipient->id,
                    'title'   => $actionTitle,
                    'body'    => "{$approverTag} approved ₹{$amount} expense for {$userName}.",
                    'screen'  => 'ExpenseList',
                    'is_read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify ExpenseFinalApproved: ' . $e->getMessage());
        }
    }

    /**
     * Notify Employee, Reporting Chain, and Approvers when an expense is rejected at Level 2.
     */
    public static function notifyExpenseFinalRejected(ExpenseBill $expense, ?string $reason): void
    {
        try {
            $expense->loadMissing(['user', 'category', 'rejectedBy', 'tempApprovedBy']);
            $user = $expense->user;
            $userName = $user?->name ?? 'Employee';
            $amount = number_format((float) $expense->amount, 2);

            $rejector = $expense->rejectedBy;
            $rejectorName = $rejector?->name ?? 'Management';
            $rejectorRole = $rejector?->roles?->first()?->name 
                ?? ($rejector?->can('expenses.final_approve') ? 'HR' : 'Management');
            $rejectorTag = "{$rejectorName} ({$rejectorRole})";
            $actionTitle = "Team Expense Rejected by {$rejectorRole}";
            $reasonText = $reason ? ": {$reason}" : '';

            // 1. Notify Submitter (Both Mobile and Web Admin Bell)
            if ($user) {
                \App\Models\UserNotification::create([
                    'user_id' => $user->id,
                    'title'   => 'Expense Claim Rejected',
                    'body'    => "Your expense of ₹{$amount} (" . ($expense->category?->name ?? 'Claim') . ") was rejected by {$rejectorName}. Reason{$reasonText}",
                    'screen'  => 'ExpenseList',
                    'is_read' => false,
                ]);

                if ($expense->rejected_by !== $user->id) {
                    $user->notify(new UserActivityNotification([
                        'title'        => 'Expense Claim Rejected',
                        'message'      => "Your expense of ₹{$amount} was rejected by {$rejectorName}. Reason{$reasonText}",
                        'category'     => 'expense',
                        'icon'         => 'x-circle',
                        'url'          => route('admin.expenses.index'),
                        'user_id'      => $user->id,
                        'user_name'    => $userName,
                        'expense_id'   => $expense->id,
                        'day_start_id' => $expense->day_start_id,
                        'amount'       => $expense->amount,
                        'created_at'   => now()->toISOString(),
                    ]));
                }
            }

            // 2. Resolve Management Chain & Permission-based Approvers
            $managers = self::getManagementChain($user);
            if ($expense->temp_approved_by && !$managers->contains('id', $expense->temp_approved_by)) {
                $tempManager = User::where('id', $expense->temp_approved_by)->where('is_active', true)->first();
                if ($tempManager) {
                    $managers->push($tempManager);
                }
            }

            $approvers = self::getExpenseFinalApprovers();

            $recipients = $managers->merge($approvers)
                ->unique('id')
                ->reject(fn($u) => $u->id === $expense->rejected_by || ($user && $u->id === $user->id));

            foreach ($recipients as $recipient) {
                // A. Web Admin Notification
                $payload = [
                    'title'        => $actionTitle,
                    'message'      => "{$rejectorTag} rejected ₹{$amount} expense for {$userName}. Reason{$reasonText}",
                    'category'     => 'expense',
                    'icon'         => 'x-circle',
                    'url'          => route('admin.expenses.index'),
                    'user_id'      => $user?->id,
                    'user_name'    => $userName,
                    'expense_id'   => $expense->id,
                    'day_start_id' => $expense->day_start_id,
                    'amount'       => $expense->amount,
                    'created_at'   => now()->toISOString(),
                ];
                $recipient->notify(new UserActivityNotification($payload));

                // B. Mobile App Notification
                \App\Models\UserNotification::create([
                    'user_id' => $recipient->id,
                    'title'   => $actionTitle,
                    'body'    => "{$rejectorTag} rejected ₹{$amount} expense for {$userName}. Reason{$reasonText}",
                    'screen'  => 'ExpenseList',
                    'is_read' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify ExpenseFinalRejected: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a payment is collected.
     */
    public static function notifyPaymentCollected(Payment $payment): void
    {
        try {
            $payment->loadMissing(['collectedBy', 'party']);
            $user = $payment->collectedBy;
            $userName = $user?->name ?? 'Field Worker';
            $partyName = $payment->party?->name ?? 'Dealer';
            $amount = number_format((float) $payment->amount, 2);
            $mode = strtoupper($payment->payment_mode ?? 'CASH');

            $payload = [
                'title'        => 'Payment Collected',
                'message'      => "{$userName} recorded payment of ₹{$amount} ({$mode}) from {$partyName}",
                'category'     => 'payment',
                'icon'         => 'banknote',
                'url'          => route('admin.payments.index'),
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'payment_id'   => $payment->id,
                'party_id'     => $payment->party_id,
                'amount'       => $payment->amount,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify PaymentCollected: ' . $e->getMessage());
        }
    }

    /**
     * Notify Admins & Manager when a leave application is applied.
     */
    public static function notifyLeaveApplied(Leave $leave): void
    {
        try {
            $leave->loadMissing(['user', 'leaveType']);
            $user = $leave->user;
            $userName = $user?->name ?? 'Employee';
            $typeName = $leave->leaveType?->name ?? 'Leave';
            $fromDate = $leave->from_date ? $leave->from_date->format('d M') : '';
            $toDate = $leave->to_date ? $leave->to_date->format('d M Y') : '';
            $dateRange = ($fromDate && $toDate && $fromDate !== $toDate) ? "({$fromDate} to {$toDate})" : "({$toDate})";

            $payload = [
                'title'        => 'Leave Application',
                'message'      => "{$userName} applied for {$typeName} {$dateRange}",
                'category'     => 'leave',
                'icon'         => 'calendar',
                'url'          => route('admin.leave.index'),
                'user_id'      => $user?->id,
                'user_name'    => $userName,
                'leave_id'     => $leave->id,
                'created_at'   => now()->toISOString(),
            ];

            self::dispatch($user, $payload);
        } catch (\Throwable $e) {
            Log::warning('ActivityNotificationService: Failed to notify LeaveApplied: ' . $e->getMessage());
        }
    }

    /**
     * Internal dispatcher that delivers notification to all eligible recipients.
     */
    protected static function dispatch(?User $actor, array $payload): void
    {
        $recipients = self::getRecipientsForUser($actor);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new UserActivityNotification($payload));
    }
}
