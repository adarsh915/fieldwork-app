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

            <form action="{{ route('admin.push-notifications.send') }}" method="POST" class="space-y-5">
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
                        <option value="Home">🏠 App Dashboard / Home</option>
                        <option value="DayStart">⏱️ Day Start / Tour Plan</option>
                        <option value="Visits">📍 Customer Visits</option>
                        <option value="Orders">🛒 Orders Screen</option>
                        <option value="Expenses">💵 Expenses Screen</option>
                        <option value="Leaves">🏖️ Leave Applications</option>
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

@endsection
