@extends('admin.layouts.app')

@section('title', 'Attendance Reports')
@section('breadcrumb', 'ADMIN / ATTENDANCE')
@section('page_title', 'Employee Attendance Calendar & Summary')

@section('content')
<div x-data="attendanceCalendarManager({
    users: {{ Js::from($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name_with_role])) }},
    initialUserId: {{ (int)$selectedUserId }},
    initialUser: {{ Js::from($selectedUser ? ['id' => $selectedUser->id, 'name' => $selectedUser->name_with_role, 'email' => $selectedUser->email] : null) }},
    initialMonthInput: '{{ $monthInput }}',
    initialYear: {{ (int)$year }},
    initialMonth: {{ (int)$month }},
    initialMonthName: '{{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}',
    initialStartDayOfWeek: {{ (int)\Carbon\Carbon::create($year, $month, 1)->dayOfWeek }},
    initialSummary: {{ Js::from($summaryData) }},
    initialCalendar: {{ Js::from($formattedCalendar) }},
    csrfToken: '{{ csrf_token() }}'
})" class="space-y-6">

    {{-- Filter & Control Panel --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            
            {{-- Employee Selector & Month Navigation --}}
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                
                {{-- Employee Select --}}
                <div class="flex items-center gap-2 min-w-[240px]">
                    <label class="form-label text-xs font-bold mb-0 whitespace-nowrap text-ink-700">Employee:</label>
                    <select x-model="selectedUserId"
                            @change="switchUser($event.target.value)"
                            class="form-input text-xs w-full py-2">
                        <template x-for="u in users" :key="u.id">
                            <option :value="u.id" :selected="u.id == selectedUserId" x-text="u.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Month Controls --}}
                <div class="flex items-center gap-1.5 bg-surface-muted p-1 rounded-xl border border-surface-border">
                    <button type="button"
                            @click="prevMonth()"
                            title="Previous Month"
                            class="p-1.5 rounded-lg bg-white text-ink-700 hover:text-indigo-600 hover:bg-slate-50 transition-colors shadow-xs">
                        <x-lucide-chevron-left class="w-4 h-4" />
                    </button>

                    <input type="month"
                           x-model="monthInput"
                           @change="switchMonth($event.target.value)"
                           class="form-input text-xs py-1.5 px-2.5 bg-white border-0 font-bold text-ink-900 w-auto">

                    <button type="button"
                            @click="nextMonth()"
                            title="Next Month"
                            class="p-1.5 rounded-lg bg-white text-ink-700 hover:text-indigo-600 hover:bg-slate-50 transition-colors shadow-xs">
                        <x-lucide-chevron-right class="w-4 h-4" />
                    </button>

                    <button type="button"
                            @click="currentMonth()"
                            class="text-xs px-2.5 py-1.5 rounded-lg bg-white text-indigo-600 hover:bg-indigo-50 font-bold transition-colors shadow-xs ml-1">
                        Current
                    </button>
                </div>

            </div>

            {{-- Audit Logs & Current View Indicator --}}
            <div class="flex items-center gap-4 w-full lg:w-auto justify-between lg:justify-end">
                <div class="text-right text-xs text-ink-500 font-medium">
                    Showing for <span class="font-bold text-ink-900" x-text="selectedUser ? selectedUser.name : 'Employee'"></span>
                    — <span class="font-bold text-indigo-600" x-text="monthName"></span>
                </div>

                @can('audit-logs.view')
                <a href="{{ route('admin.audit-logs.index') }}"
                   class="btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1.5">
                    <x-lucide-history class="w-3.5 h-3.5 text-ink-500" />
                    Audit Logs
                </a>
                @endcan
            </div>

        </div>
    </div>

    {{-- Summary Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        {{-- Present --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-emerald-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Present</p>
                <h3 class="text-2xl font-extrabold text-emerald-600 mt-1" x-text="summary.present"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                <x-lucide-check-circle class="w-5 h-5" />
            </div>
        </div>

        {{-- Half Day / Short Shift --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-amber-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Half / Short</p>
                <h3 class="text-2xl font-extrabold text-amber-600 mt-1" x-text="summary.half_day_total"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                <x-lucide-clock class="w-5 h-5" />
            </div>
        </div>

        {{-- On Leave --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-blue-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">On Leave</p>
                <h3 class="text-2xl font-extrabold text-blue-600 mt-1" x-text="summary.on_leave"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100">
                <x-lucide-palmtree class="w-5 h-5" />
            </div>
        </div>

        {{-- Absent --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-rose-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Absent</p>
                <h3 class="text-2xl font-extrabold text-rose-600 mt-1" x-text="summary.absent"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                <x-lucide-x-circle class="w-5 h-5" />
            </div>
        </div>

        {{-- Holidays / Off --}}
        <div class="panel p-4 flex items-center justify-between border-l-4 border-l-purple-500 shadow-sm rounded-2xl bg-white transition-all">
            <div>
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Holidays / Off</p>
                <h3 class="text-2xl font-extrabold text-purple-600 mt-1" x-text="summary.holiday_off_total"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                <x-lucide-calendar-days class="w-5 h-5" />
            </div>
        </div>
    </div>

    {{-- Calendar Grid Panel --}}
    <div class="panel p-6 border border-surface-border shadow-sm rounded-2xl relative overflow-hidden">
        
        {{-- Loading Overlay --}}
        <div x-show="loading"
             x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-white/75 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center gap-3">
            <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xs font-bold text-ink-600">Updating calendar records...</span>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                <x-lucide-calendar class="w-5 h-5 text-indigo-600" />
                <span>Monthly Attendance Calendar — </span>
                <span class="text-indigo-600" x-text="selectedUser ? selectedUser.name : ''"></span>
                <span class="text-ink-400 font-normal text-sm" x-text="`(${monthName})`"></span>
            </h3>
        </div>

        {{-- Weekday Headers --}}
        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px;"
             class="mb-3 text-center text-xs font-extrabold uppercase tracking-wider bg-surface-muted p-2.5 rounded-xl border border-surface-border">
            <div class="text-rose-600">Sun</div>
            <div class="text-ink-700">Mon</div>
            <div class="text-ink-700">Tue</div>
            <div class="text-ink-700">Wed</div>
            <div class="text-ink-700">Thu</div>
            <div class="text-ink-700">Fri</div>
            <div class="text-ink-700">Sat</div>
        </div>

        {{-- Days Grid --}}
        <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px;">
            {{-- Empty Padding Cells before the 1st of the month --}}
            <template x-for="p in startDayOfWeek" :key="`pad-${p}`">
                <div class="min-h-[95px] bg-slate-50/50 rounded-xl border border-dashed border-surface-border/60"></div>
            </template>

            {{-- Actual Days --}}
            <template x-for="day in calendar" :key="day.date">
                <div class="min-h-[95px] p-3 rounded-xl border transition-all flex flex-col justify-between group relative"
                     :class="{
                         'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/20': day.is_today,
                         'bg-rose-50/25 border-surface-border': day.is_sunday && !day.is_today,
                         'bg-white border-surface-border hover:border-indigo-200 hover:shadow-xs': !day.is_sunday && !day.is_today
                     }">

                    {{-- Day Number & Action Bar --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold"
                              :class="day.is_sunday ? 'text-rose-600' : (day.is_today ? 'text-indigo-600' : 'text-ink-900')"
                              x-text="day.day"></span>

                        <div class="flex items-center gap-1">
                            <template x-if="day.is_auto_closed">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 cursor-help"
                                      :title="day.flagged_reason || 'Auto-closed by system'">
                                    ⚠️ AUTO
                                </span>
                            </template>

                            <button type="button"
                                    @click="openAdjustModal(day)"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-ink-400 hover:text-indigo-600 hover:bg-indigo-50 rounded"
                                    title="Correct / Adjust Attendance">
                                <x-lucide-pencil class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="mt-2">
                        {{-- Present --}}
                        <template x-if="day.status === 'present'">
                            <div>
                                <span class="badge badge-success w-full justify-center text-[11px] font-bold py-1">✓ Present</span>
                                <template x-if="day.hours">
                                    <span class="text-[10px] text-emerald-700 font-mono block text-center mt-1 font-semibold" x-text="`${day.hours} hrs`"></span>
                                </template>
                            </div>
                        </template>

                        {{-- Half Day --}}
                        <template x-if="day.status === 'half_day_present'">
                            <div>
                                <span class="badge badge-warning w-full justify-center text-[11px] font-bold py-1">½ Half Day</span>
                                <template x-if="day.hours">
                                    <span class="text-[10px] text-amber-700 font-mono block text-center mt-1 font-semibold" x-text="`${day.hours} hrs`"></span>
                                </template>
                            </div>
                        </template>

                        {{-- Short Shift --}}
                        <template x-if="day.status === 'short_shift'">
                            <span class="px-2 py-1 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 block text-center">
                                ⚠️ Short Shift
                            </span>
                        </template>

                        {{-- On Leave --}}
                        <template x-if="day.status === 'on_leave'">
                            <span class="badge badge-warning w-full justify-center text-[11px] font-bold py-1 truncate"
                                  :title="day.label"
                                  x-text="`🏖 ${day.label}`"></span>
                        </template>

                        {{-- Holiday --}}
                        <template x-if="day.status === 'holiday'">
                            <span class="badge badge-primary w-full justify-center text-[11px] font-bold py-1 truncate"
                                  :title="day.label"
                                  x-text="`⚑ ${day.label}`"></span>
                        </template>

                        {{-- Weekly Off --}}
                        <template x-if="day.status === 'weekly_off'">
                            <span class="px-2 py-1 rounded-md text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 block text-center">
                                ☽ Week Off
                            </span>
                        </template>

                        {{-- Absent --}}
                        <template x-if="day.status === 'absent'">
                            <span class="badge badge-danger w-full justify-center text-[11px] font-bold py-1">✗ Absent</span>
                        </template>

                        {{-- Not Yet / Future --}}
                        <template x-if="day.status === 'not_yet'">
                            <span class="text-[11px] text-ink-400 text-center block">—</span>
                        </template>
                    </div>

                </div>
            </template>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: ADMIN MANUAL CORRECTION (AJAX)                                     --}}
    {{-- ========================================================================= --}}
    <div x-show="adjustModal.open" x-cloak style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="adjustModal.open = false">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            
            {{-- Backdrop --}}
            <div x-show="adjustModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="adjustModal.open = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

            {{-- Dialog --}}
            <div x-show="adjustModal.open"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-left border border-surface-border">
                
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-surface-border">
                    <div>
                        <h3 class="text-base font-bold text-ink-900 flex items-center gap-2">
                            <x-lucide-pencil class="w-4 h-4 text-indigo-600" />
                            Correct Attendance Record
                        </h3>
                        <p class="text-xs text-ink-500 mt-0.5">
                            For <span class="font-bold text-ink-900" x-text="selectedUser ? selectedUser.name : 'Employee'"></span>
                            on <span class="font-bold text-indigo-600" x-text="adjustModal.date"></span>
                        </p>
                    </div>
                    <button type="button" @click="adjustModal.open = false" class="text-ink-400 hover:text-ink-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAdjust()" class="space-y-4">
                    {{-- Status Selection --}}
                    <div>
                        <label class="form-label text-xs font-bold">New Attendance Status <span class="text-rose-500">*</span></label>
                        <select x-model="adjustModal.new_status" required class="form-input text-xs w-full">
                            <option value="present">Present (Full Day 1.0)</option>
                            <option value="half_day_present">Half Day Present (0.5)</option>
                            <option value="short_shift">Short Shift / Flagged</option>
                            <option value="on_leave">On Leave</option>
                            <option value="holiday">Holiday</option>
                            <option value="weekly_off">Weekly Off</option>
                            <option value="absent">Absent</option>
                        </select>
                        <template x-if="adjustModal.errors.new_status">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="adjustModal.errors.new_status[0]"></p>
                        </template>
                    </div>

                    {{-- Correction Reason --}}
                    <div>
                        <label class="form-label text-xs font-bold">Correction Rationale / Reason <span class="text-rose-500">*</span></label>
                        <textarea x-model="adjustModal.reason"
                                  rows="3"
                                  required
                                  placeholder="Explain why this correction is being made (Audit log requirement)..."
                                  class="form-input text-xs w-full"
                                  :class="adjustModal.errors.reason ? 'border-rose-500 ring-1 ring-rose-200' : ''"></textarea>
                        <template x-if="adjustModal.errors.reason">
                            <p class="mt-1 text-xs text-rose-600 font-semibold" x-text="adjustModal.errors.reason[0]"></p>
                        </template>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-2.5 pt-3 border-t border-surface-border">
                        <button type="button" @click="adjustModal.open = false" :disabled="adjustModal.saving" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button type="submit" :disabled="adjustModal.saving"
                                class="btn-primary text-xs inline-flex items-center gap-1.5"
                                style="background-color: #4f46e5; color: #ffffff;">
                            <svg x-show="adjustModal.saving" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="adjustModal.saving ? 'Saving...' : 'Save Correction'"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function attendanceCalendarManager(config) {
    return {
        users: config.users || [],
        selectedUserId: config.initialUserId,
        selectedUser: config.initialUser,
        monthInput: config.initialMonthInput,
        year: config.initialYear,
        month: config.initialMonth,
        monthName: config.initialMonthName,
        startDayOfWeek: config.initialStartDayOfWeek,
        summary: config.initialSummary || {},
        calendar: config.initialCalendar || [],
        csrfToken: config.csrfToken,

        loading: false,

        adjustModal: {
            open: false,
            saving: false,
            date: '',
            new_status: 'present',
            reason: '',
            errors: {}
        },

        async fetchCalendarData() {
            this.loading = true;
            try {
                const url = `/admin/attendance?user_id=${this.selectedUserId}&month=${this.monthInput}`;
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedUser = data.user;
                    this.year = data.year;
                    this.month = data.month;
                    this.monthInput = data.month_input;
                    this.monthName = data.month_name;
                    this.startDayOfWeek = data.start_day_of_week;
                    this.summary = data.summary;
                    this.calendar = data.calendar;

                    // Update URL without page reload
                    window.history.pushState(null, '', url);
                } else {
                    window.toast('Failed to load attendance data.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error loading attendance calendar.', 'error');
            } finally {
                this.loading = false;
            }
        },

        switchUser(userId) {
            this.selectedUserId = parseInt(userId);
            this.fetchCalendarData();
        },

        switchMonth(newMonth) {
            if (!newMonth) return;
            this.monthInput = newMonth;
            this.fetchCalendarData();
        },

        prevMonth() {
            const [y, m] = this.monthInput.split('-').map(Number);
            const prev = new Date(y, m - 2, 1);
            const prevYear = prev.getFullYear();
            const prevMonth = String(prev.getMonth() + 1).padStart(2, '0');
            this.monthInput = `${prevYear}-${prevMonth}`;
            this.fetchCalendarData();
        },

        nextMonth() {
            const [y, m] = this.monthInput.split('-').map(Number);
            const next = new Date(y, m, 1);
            const nextYear = next.getFullYear();
            const nextMonth = String(next.getMonth() + 1).padStart(2, '0');
            this.monthInput = `${nextYear}-${nextMonth}`;
            this.fetchCalendarData();
        },

        currentMonth() {
            const today = new Date();
            const curYear = today.getFullYear();
            const curMonth = String(today.getMonth() + 1).padStart(2, '0');
            this.monthInput = `${curYear}-${curMonth}`;
            this.fetchCalendarData();
        },

        openAdjustModal(day) {
            this.adjustModal.date = day.date;
            this.adjustModal.new_status = (day.status && day.status !== 'not_yet') ? day.status : 'present';
            this.adjustModal.reason = '';
            this.adjustModal.errors = {};
            this.adjustModal.open = true;
        },

        async submitAdjust() {
            this.adjustModal.saving = true;
            this.adjustModal.errors = {};

            try {
                const res = await fetch(`{{ route('admin.attendance.adjust') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        user_id: this.selectedUserId,
                        date: this.adjustModal.date,
                        new_status: this.adjustModal.new_status,
                        reason: this.adjustModal.reason
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    // Update single day in calendar array
                    const idx = this.calendar.findIndex(d => d.date === this.adjustModal.date);
                    if (idx > -1 && data.day) {
                        this.calendar[idx] = data.day;
                    }
                    if (data.summary) {
                        this.summary = data.summary;
                    }
                    this.adjustModal.open = false;
                    window.toast(data.message || 'Attendance adjusted successfully!', 'success');
                } else if (res.status === 422 && data.errors) {
                    this.adjustModal.errors = data.errors;
                    window.toast(data.message || 'Please fix validation errors.', 'error');
                } else {
                    window.toast(data.message || 'Failed to adjust attendance.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving correction.', 'error');
            } finally {
                this.adjustModal.saving = false;
            }
        }
    };
}
</script>
@endpush
