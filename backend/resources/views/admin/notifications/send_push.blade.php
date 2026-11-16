@extends('admin.layouts.app')

@section('title', 'Send Push Notifications')
@section('breadcrumb', 'NOTIFICATIONS / PUSH')
@section('page_title', 'Send Push Notifications to Mobile App')

@section('content')

@if(session('status'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-emerald-600 shrink-0" />
        <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
        <x-lucide-alert-circle class="w-5 h-5 text-rose-600 shrink-0" />
        <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Left 2 Columns: Push Notification Composer ── --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="panel p-6 border-t-4 border-indigo-600 shadow-sm">
            <div class="flex items-center justify-between mb-5 border-b pb-4">
                <div>
                    <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                        <x-lucide-send class="w-5 h-5 text-indigo-600" />
                        Compose Push Notification
                    </h2>
                    <p class="text-caption mt-1">Broadcast direct push alerts to mobile phones via Firebase Cloud Messaging.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        FCM Connected
                    </span>
                </div>
            </div>

            <form id="pushNotificationForm" action="{{ route('admin.push-notifications.send') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Recipient Selection --}}
                <div>
                    <label class="form-label font-semibold text-ink-800">Target Recipient <span class="text-rose-500">*</span></label>
                    <select name="recipient" required class="form-select w-full border-ink-300 rounded-lg py-2.5">
                        <optgroup label="Broadcast Groups">
                            <option value="all">📢 All Active Users with App ({{ $tokenCount }})</option>
                            <option value="field_workers" selected>🏃 All Field Workers</option>
                        </optgroup>
                        <optgroup label="Individual Employees">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name_with_role }} {{ !empty($user->fcm_token) ? '📱 (App Active)' : '⚠️ (No Device Registered)' }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                {{-- Title --}}
                <div>
                    <label class="form-label font-semibold text-ink-800">Notification Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required maxlength="150"
                           placeholder="e.g. Urgent Notice: Team Meeting Today"
                           class="form-input w-full border-ink-300 rounded-lg py-2.5 font-medium" />
                </div>

                {{-- Message Body --}}
                <div>
                    <label class="form-label font-semibold text-ink-800">Notification Message <span class="text-rose-500">*</span></label>
                    <textarea name="body" rows="4" required maxlength="500"
                              placeholder="Type the message that will pop up on the employee's phone screen..."
                              class="form-input w-full border-ink-300 rounded-lg py-2.5"></textarea>
                </div>

                {{-- Target Screen Deep-Link (Optional) --}}
                <div>
                    <label class="form-label font-semibold text-ink-800">Tap Action / Open Screen</label>
                    <select name="screen" class="form-select w-full border-ink-300 rounded-lg py-2.5">
                        <option value="Home">App Dashboard / Home</option>
                        <option value="DayStart">Day Start / Tour Plan</option>
                        <option value="Visits">Customer Visits</option>
                        <option value="Orders">Orders Screen</option>
                        <option value="Expenses">Expenses Screen</option>
                        <option value="Leaves">Leave Applications</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">When the user taps the notification on their phone, it will open this screen directly.</p>
                </div>

                {{-- Submit Button --}}
                <div class="pt-3 border-t flex justify-end">
                    <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
                        <x-lucide-send class="w-4 h-4" />
                        Send Push Notification Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Right Column: Device Status Overview ── --}}
    <div class="space-y-6">
        <div class="panel p-5 border shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wider text-ink-600 mb-4 flex items-center gap-2">
                <x-lucide-smartphone class="w-4 h-4 text-ink-500" />
                Registered Mobile Devices
            </h3>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                    <p class="text-xs text-emerald-700 font-medium">Devices Connected</p>
                    <p class="text-2xl font-black text-emerald-900 mt-0.5">{{ $tokenCount }}</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-700 font-medium">Total Employees</p>
                    <p class="text-2xl font-black text-slate-900 mt-0.5">{{ $users->count() }}</p>
                </div>
            </div>

            <p class="text-xs text-ink-500 leading-relaxed">
                When a field worker logs in on the mobile app, their device automatically registers its FCM push token.
            </p>

            <div class="mt-4 max-h-[380px] overflow-y-auto space-y-2 border-t pt-3">
                @foreach($users as $u)
                    <div class="flex items-center justify-between p-2.5 rounded-lg text-xs bg-surface-50 hover:bg-surface-100 border">
                        <div class="min-w-0 pr-2">
                            <p class="font-semibold text-ink-900 truncate">{{ $u->name }}</p>
                            <p class="text-ink-500 text-[11px]">{{ $u->role_name }}</p>
                        </div>
                        @if(!empty($u->fcm_token))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                📱 Registered
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                Not Registered
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- ── Bottom Section: Sent Notification History ── --}}
<div class="mt-8">
    <div class="panel p-6 border shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-surface-200 gap-3">
            <div>
                <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-history class="w-5 h-5 text-indigo-600" />
                    Sent Notification History
                </h3>
                <p class="text-xs text-ink-500 mt-1">Chronological audit log of all broadcast campaigns and direct mobile alerts.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Total Sent: {{ $logs->total() }}
                </span>
            </div>
        </div>

        @if($logs->isEmpty())
            <div class="py-12 flex flex-col items-center justify-center text-center">
                <div class="w-12 h-12 rounded-full bg-surface-100 flex items-center justify-center text-ink-400 mb-3">
                    <x-lucide-bell-off class="w-6 h-6" />
                </div>
                <h4 class="text-sm font-semibold text-ink-800">No Notifications Sent Yet</h4>
                <p class="text-xs text-ink-500 mt-1 max-w-sm">When you compose and broadcast push notifications from the form above, their delivery logs will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-surface-200 bg-surface-50 text-ink-500 uppercase font-semibold text-[11px] tracking-wider">
                            <th class="py-3 px-3">Date & Time</th>
                            <th class="py-3 px-3">Target Audience</th>
                            <th class="py-3 px-3">Notification Content</th>
                            <th class="py-3 px-3">Target Screen</th>
                            <th class="py-3 px-3 text-center">Recipients / Reach</th>
                            <th class="py-3 px-3">Sent By</th>
                            <th class="py-3 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 text-ink-700">
                        @foreach($logs as $log)
                            <tr class="hover:bg-surface-50 transition-colors">
                                {{-- Date & Time --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-semibold text-ink-900">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-ink-500">{{ $log->created_at->format('h:i A') }} ({{ $log->created_at->diffForHumans() }})</div>
                                </td>

                                {{-- Target Audience --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($log->recipient_type === 'all')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            📢 All Active Users
                                        </span>
                                    @elseif($log->recipient_type === 'field_workers')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            🏃 Field Workers
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                            👤 {{ $log->recipientUser?->name ?? 'Single User' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Notification Content --}}
                                <td class="py-3 px-3 max-w-xs">
                                    <div class="font-bold text-ink-900 truncate">{{ $log->title }}</div>
                                    <div class="text-[11px] text-ink-500 line-clamp-2 mt-0.5">{{ $log->body }}</div>
                                </td>

                                {{-- Target Screen --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-surface-100 text-ink-700 border border-surface-200">
                                        {{ $log->screen ?? 'Home' }}
                                    </span>
                                </td>

                                {{-- Recipients / Reach --}}
                                <td class="py-3 px-3 whitespace-nowrap text-center">
                                    <div class="font-semibold text-ink-900">{{ $log->total_recipients }} {{ \Illuminate\Support\Str::plural('user', $log->total_recipients) }}</div>
                                    <div class="text-[11px] text-emerald-700 font-medium">{{ $log->devices_reached }} devices active</div>
                                </td>

                                {{-- Sent By --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-ink-800">{{ $log->sender?->name ?? 'Administrator' }}</div>
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3 px-3 whitespace-nowrap text-center">
                                    @if($log->status === 'delivered')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Delivered
                                        </span>
                                    @elseif($log->status === 'partial')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300" title="{{ $log->error_message }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            Partial (No Token)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300" title="{{ $log->error_message }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            Failed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pushNotificationForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const origHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">↻</span> Sending Push Alerts...';

        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                if (window.toast) window.toast(data.message || 'Push notifications sent successfully!', 'success');
                // Clear message fields but preserve recipient selection
                form.querySelector('input[name="title"]').value = '';
                form.querySelector('textarea[name="body"]').value = '';
                // Reload after brief delay to show new entry in history table
                setTimeout(() => window.location.reload(), 1200);
            } else {
                let msg = data.message || 'Failed to send notification';
                if (data.errors) msg = Object.values(data.errors).flat().join('<br>');
                if (window.toast) window.toast(msg, 'error');
            }
        } catch (err) {
            console.error(err);
            if (window.toast) window.toast('Network error. Failed to reach server.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
        }
    });
});
</script>
@endpush
