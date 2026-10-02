@extends('admin.layouts.app')

@section('title', 'System Settings')
@section('breadcrumb', 'ADMIN / SETTINGS')
@section('page_title', 'System-Wide Rules & Settings')

@section('content')

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
        <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
        <p class="text-sm font-medium text-success-text">{{ session('success') }}</p>
    </div>
@endif

<div class="space-y-6">

    {{-- ── 1. Company Payment & Official UPI Configuration ── --}}
    <div class="panel p-6 border-l-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-banknote class="w-5 h-5 text-emerald-600" />
                    Company Payment & Official UPI Configuration
                </h2>
                <p class="text-caption mt-1">Configure official company accounts. Field reps will display this exact company UPI ID / details to dealers.</p>
            </div>
            <div class="text-xs text-emerald-800 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200 font-semibold">
                🔒 Security Rule: Field reps never use personal accounts.
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Official Company UPI ID <span class="text-rose-500">*</span></label>
                    <input type="text" name="company_upi_id"
                           value="{{ $settings['company_upi_id']['setting_value'] ?? 'amplindustries@icici' }}"
                           required placeholder="e.g. amplindustries@icici" class="form-input font-mono" />
                    <p class="text-xs text-ink-500 mt-1">Displayed in the mobile app when rep selects UPI mode for dealer scan.</p>
                </div>

                <div>
                    <label class="form-label">Company Account Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="company_name"
                           value="{{ $settings['company_name']['setting_value'] ?? 'AMPL Industries Ltd' }}"
                           required placeholder="e.g. AMPL Industries Ltd" class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Official registered entity name.</p>
                </div>

                <div>
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="company_bank_name"
                           value="{{ $settings['company_bank_name']['setting_value'] ?? 'ICICI Bank' }}"
                           placeholder="e.g. ICICI Bank" class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Bank where dealer payments are deposited.</p>
                </div>

                <div>
                    <label class="form-label">Company Bank Account No</label>
                    <input type="text" name="company_account_no"
                           value="{{ $settings['company_account_no']['setting_value'] ?? '001105001234' }}"
                           placeholder="e.g. 001105001234" class="form-input font-mono" />
                    <p class="text-xs text-ink-500 mt-1">Current account number for NEFT/RTGS/IMPS.</p>
                </div>

                <div>
                    <label class="form-label">Bank IFSC Code</label>
                    <input type="text" name="company_ifsc_code"
                           value="{{ $settings['company_ifsc_code']['setting_value'] ?? 'ICIC0000011' }}"
                           placeholder="e.g. ICIC0000011" class="form-input font-mono uppercase" />
                    <p class="text-xs text-ink-500 mt-1">11-character bank branch code.</p>
                </div>

                <div>
                    <label class="form-label">Bank Branch</label>
                    <input type="text" name="company_branch"
                           value="{{ $settings['company_branch']['setting_value'] ?? 'Head Office Branch' }}"
                           placeholder="e.g. Head Office Branch" class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Physical branch address.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Payment Settings
                </x-button>
            </div>
        </form>
    </div>

    {{-- ── 2. Attendance & Shift Threshold Rules ── --}}
    <div class="panel p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-calendar-check class="w-5 h-5 text-primary-600" />
                    Attendance & Shift Threshold Rules
                </h2>
                <p class="text-caption mt-1">Configure hours required for Full/Half day presence, auto-close time, and retroactive leave limits.</p>
            </div>
            <div class="text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200">
                ⚡ Note: Setting changes take effect for <strong>future date evaluations</strong>.
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Full-Day Present Threshold (Hours) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="full_day_hours_threshold"
                           value="{{ $settings['full_day_hours_threshold']['setting_value'] ?? '8' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Minimum hours worked (DayEnd - DayStart) to count as 1.0 Full Day Present.</p>
                </div>

                <div>
                    <label class="form-label">Half-Day Present Threshold (Hours) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.5" name="half_day_hours_threshold"
                           value="{{ $settings['half_day_hours_threshold']['setting_value'] ?? '4' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Minimum hours worked to count as 0.5 Half Day Present. (Below this is Short Shift).</p>
                </div>

                <div>
                    <label class="form-label">DayStart Auto-Close Cutoff Time <span class="text-rose-500">*</span></label>
                    <input type="time" name="auto_close_time" step="1"
                           value="{{ $settings['auto_close_time']['setting_value'] ?? '23:59:59' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Daily scheduled time when open DayStart records are auto-closed.</p>
                </div>

                <div>
                    <label class="form-label">Retroactive Past Leave Days Limit <span class="text-rose-500">*</span></label>
                    <input type="number" name="retroactive_leave_days_allowed"
                           value="{{ $settings['retroactive_leave_days_allowed']['setting_value'] ?? '7' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Maximum past days an employee can select when applying for retroactive leave.</p>
                </div>

                <div>
                    <label class="form-label">Working Days Per Week <span class="text-rose-500">*</span></label>
                    <input type="number" name="working_days_per_week"
                           value="{{ $settings['working_days_per_week']['setting_value'] ?? '6' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Standard weekly working days (e.g. 6 days = Mon-Sat).</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Attendance Settings
                </x-button>
            </div>
        </form>
    </div>

    {{-- ── 3. Expense & Reimbursement Policy Rules ── --}}
    <div class="panel p-6 border-l-4 border-indigo-500">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-receipt class="w-5 h-5 text-indigo-600" />
                    Expense & Reimbursement Policy Rules
                </h2>
                <p class="text-caption mt-1">Configure limits and grace periods for fieldworker expense submissions.</p>
            </div>
            <div class="text-xs text-indigo-800 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-200 font-semibold">
                💼 Audit Rule: Post-day-end and backdated claims are flagged for manager audit.
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Past Days Allowed for Expenses <span class="text-rose-500">*</span></label>
                    <input type="number" name="expense_past_days_allowed" min="0" max="90"
                           value="{{ $settings['expense_past_days_allowed']['setting_value'] ?? '7' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">Maximum number of past calendar days allowed to backdate/submit an expense claim (0 = today only, max 90).</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Expense Settings
                </x-button>
            </div>
        </form>
    </div>

    {{-- ── 4. Sales & Order Policy Rules ── --}}
    <div class="panel p-6 border-l-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-shopping-cart class="w-5 h-5 text-emerald-600" />
                    Sales & Order Policy Rules
                </h2>
                <p class="text-caption mt-1">Configure phone/direct ordering permissions for field workers.</p>
            </div>
            <div class="text-xs text-emerald-800 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200 font-semibold">
                📞 Policy Control: Turn on/off remote phone ordering without requiring store check-in.
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Allow Direct / Phone Orders <span class="text-rose-500">*</span></label>
                    @php 
                        $phoneOrdersAllowed = (string)($settings['allow_phone_orders']['setting_value'] ?? '1');
                    @endphp
                    <select name="allow_phone_orders" class="form-input">
                        <option value="1" {{ in_array($phoneOrdersAllowed, ['1', 'true', 'on', 'yes'], true) ? 'selected' : '' }}>
                            ✓ Enabled — Field workers can book direct / phone orders without a physical store visit
                        </option>
                        <option value="0" {{ in_array($phoneOrdersAllowed, ['0', 'false', 'off', 'no'], true) ? 'selected' : '' }}>
                            ✕ Disabled — Mandatory physical store visit (Check-in) required for all orders
                        </option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">When disabled, field workers cannot submit orders without an active physical store visit.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Order Policy Settings
                </x-button>
            </div>
        </form>
    </div>

</div>

@endsection
