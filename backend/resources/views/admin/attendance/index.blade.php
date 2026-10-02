@extends('admin.layouts.app')

@section('title', 'Attendance Reports')
@section('breadcrumb', 'ADMIN / ATTENDANCE')
@section('page_title', 'Employee Attendance Calendar & Summary')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

<div x-data="{
    showModal: false,
    adjUser: '{{ $selectedUserId }}',
    adjDate: '',
    adjStatus: 'present',
    adjReason: '',
    openModal(date, currentStatus) {
        this.adjDate = date;
        this.adjStatus = currentStatus || 'present';
        this.adjReason = '';
        this.showModal = true;
    }
}">

    {{-- Filter Panel --}}
    <div class="panel p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-ink-900">Attendance Filter</h2>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-brand-600 hover:underline font-semibold flex items-center gap-1">
                <x-lucide-history class="w-3.5 h-3.5" /> View System Audit Logs
            </a>
        </div>
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="flex flex-wrap gap-4 items-end justify-between">
            <div class="flex flex-wrap gap-4 items-end flex-1">
                <div class="w-full md:w-1/3">
                    <label class="form-label">Select Employee <span class="text-rose-500">*</span></label>
                    <select name="user_id" onchange="this.form.submit()" class="form-input">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->employee_id ?? 'ID: ' . $u->id }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="form-label">Select Month</label>
                    <input type="month" name="month" value="{{ $monthInput }}" onchange="this.form.submit()" class="form-input" />
                </div>

                <x-button type="submit" variant="primary">View Calendar</x-button>
            </div>

            <div class="text-right text-xs text-ink-500 font-medium">
                Showing for <span class="font-bold text-ink-900">{{ $selectedUser->name ?? 'Employee' }}</span> — {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
            </div>
        </form>
    </div>

    @if($selectedUser)
    {{-- Summary Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-emerald-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase">Present</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $summary['present'] }}</h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                <x-lucide-check-circle class="w-5 h-5" />
            </div>
        </div>

        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-amber-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase">Half Day / Short</p>
                <h3 class="text-2xl font-extrabold text-amber-600 mt-1">{{ ($summary['half_day_present'] ?? 0) + ($summary['short_shift'] ?? 0) }}</h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                <x-lucide-clock class="w-5 h-5" />
            </div>
        </div>

        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-blue-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase">On Leave</p>
                <h3 class="text-2xl font-extrabold text-blue-600 mt-1">{{ $summary['on_leave'] }}</h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <x-lucide-palmtree class="w-5 h-5" />
            </div>
        </div>

        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-rose-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase">Absent</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1">{{ $summary['absent'] }}</h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                <x-lucide-x-circle class="w-5 h-5" />
            </div>
        </div>

        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-purple-500">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase">Holidays / Off</p>
                <h3 class="text-2xl font-extrabold text-purple-600 mt-1">{{ $summary['holiday'] + $summary['weekly_off'] }}</h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                <x-lucide-calendar-days class="w-5 h-5" />
            </div>
        </div>
    </div>

    {{-- Calendar Grid Panel --}}
    <div class="panel p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                <x-lucide-calendar class="w-5 h-5 text-brand-600" />
                Monthly Attendance Calendar — {{ $selectedUser->name }} ({{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }})
            </h3>
        </div>

        @php
            $firstDate = \Carbon\Carbon::create($year, $month, 1);
            $startDayOfWeek = $firstDate->dayOfWeek;
        @endphp

        {{-- Weekday Headers --}}
        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px;" class="mb-3 text-center text-xs font-extrabold text-ink-600 uppercase tracking-wider bg-surface-muted p-2.5 rounded-lg border border-surface-border">
            <div class="text-rose-600">Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
        </div>

        {{-- Days Grid --}}
        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px;">
            @for($p = 0; $p < $startDayOfWeek; $p++)
                <div class="min-h-[85px] bg-surface-muted/20 rounded-xl border border-dashed border-surface-border/50"></div>
            @endfor

            @foreach($calendar as $dateStr => $dayInfo)
                @php
                    $d = \Carbon\Carbon::parse($dateStr);
                    $isToday = $d->isToday();
                    $st = $dayInfo['status'];
                    $dayStartObj = $dayInfo['day_start'] ?? null;
                    $isAutoClosed = $dayStartObj?->is_auto_closed ?? false;
                @endphp
                <div class="min-h-[95px] p-3 rounded-xl border transition-all flex flex-col justify-between group relative
                    {{ $isToday ? 'ring-2 ring-brand-500 border-brand-500 bg-brand-50/30' : ($d->isSunday() ? 'bg-rose-50/20 border-surface-border' : 'bg-white border-surface-border') }}">

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold {{ $d->isSunday() ? 'text-rose-600' : 'text-ink-900' }}">
                            {{ $d->day }}
                        </span>
                        
                        <div class="flex items-center gap-1">
                            @if($isAutoClosed)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300" title="{{ $dayStartObj->flagged_reason }}">
                                    ⚠️ AUTO
                                </span>
                            @endif
                            
                            <button type="button" @click="openModal('{{ $dateStr }}', '{{ $st }}')"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-ink-400 hover:text-brand-600 hover:bg-surface-hover rounded"
                                    title="Edit / Correct Date Attendance">
                                <x-lucide-pencil class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-2">
                        @if($st === 'present')
                            <span class="badge badge-success w-full justify-center text-[11px] font-bold py-1">✓ Present</span>
                            @if(isset($dayInfo['hours']))
                                <span class="text-[10px] text-emerald-700 font-mono block text-center mt-1">{{ $dayInfo['hours'] }} hrs</span>
                            @endif
                        @elseif($st === 'half_day_present')
                            <span class="badge badge-warning w-full justify-center text-[11px] font-bold py-1">½ Half Day</span>
                            @if(isset($dayInfo['hours']))
                                <span class="text-[10px] text-amber-700 font-mono block text-center mt-1">{{ $dayInfo['hours'] }} hrs</span>
                            @endif
                        @elseif($st === 'short_shift')
                            <span class="px-2 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 block text-center">⚠️ Short Shift</span>
                        @elseif($st === 'on_leave')
                            <span class="badge badge-warning w-full justify-center text-[11px] font-bold py-1 truncate">🏖 {{ $dayInfo['label'] }}</span>
                        @elseif($st === 'holiday')
                            <span class="badge badge-primary w-full justify-center text-[11px] font-bold py-1 truncate" title="{{ $dayInfo['label'] }}">⚑ {{ \Illuminate\Support\Str::limit($dayInfo['label'], 12) }}</span>
                        @elseif($st === 'weekly_off')
                            <span class="px-2 py-1 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 block text-center">☽ Week Off</span>
                        @elseif($st === 'absent')
                            <span class="badge badge-danger w-full justify-center text-[11px] font-bold py-1">✗ Absent</span>
                        @else
                            <span class="text-[11px] text-ink-400 text-center block">—</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Admin Manual Correction Modal --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl border border-surface-border" @click.away="showModal = false">
            <h3 class="text-lg font-bold text-ink-900 mb-1">Correct Attendance Record</h3>
            <p class="text-xs text-ink-500 mb-4">Manually adjust attendance status for date <span x-text="adjDate" class="font-bold text-ink-900"></span>.</p>

            <form action="{{ route('admin.attendance.adjust') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" :value="adjUser" />
                <input type="hidden" name="date" :value="adjDate" />

                <div class="space-y-4">
                    <div>
                        <label class="form-label">New Attendance Status <span class="text-rose-500">*</span></label>
                        <select name="new_status" x-model="adjStatus" class="form-input" required>
                            <option value="present">Present (Full Day 1.0)</option>
                            <option value="half_day_present">Half Day Present (0.5)</option>
                            <option value="short_shift">Short Shift / Flagged</option>
                            <option value="on_leave">On Leave</option>
                            <option value="holiday">Holiday</option>
                            <option value="weekly_off">Weekly Off</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Correction Rationale / Reason <span class="text-rose-500">*</span></label>
                        <textarea name="reason" x-model="adjReason" rows="3" required placeholder="Explain why this correction is being made (Audit log requirement)..." class="form-input"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-surface-border">
                    <x-button type="button" variant="secondary" @click="showModal = false">Cancel</x-button>
                    <x-button type="submit" variant="primary">Save Correction</x-button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
