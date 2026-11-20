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

<div class="space-y-6" x-data="settingsFormHandler()">

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
            <div class="text-xs text-emerald-800 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200 font-semibold flex items-center gap-1.5">
                <x-lucide-shield-check class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Security Rule: Field reps never use personal accounts.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
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
            <div class="text-xs text-amber-800 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200 font-medium flex items-center gap-1.5">
                <x-lucide-alert-triangle class="w-4 h-4 text-amber-600 shrink-0" />
                <span>Note: Setting changes take effect for <strong>future date evaluations</strong>.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
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
            <div class="text-xs text-indigo-800 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-200 font-semibold flex items-center gap-1.5">
                <x-lucide-clipboard-check class="w-4 h-4 text-indigo-600 shrink-0" />
                <span>Audit Rule: Post-day-end and backdated claims are flagged for manager audit.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
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
            <div class="text-xs text-emerald-800 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200 font-semibold flex items-center gap-1.5">
                <x-lucide-phone-call class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Policy Control: Turn on/off remote phone ordering without requiring store check-in.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Allow Direct / Phone Orders <span class="text-rose-500">*</span></label>
                    @php 
                        $phoneOrdersAllowed = (string)($settings['allow_phone_orders']['setting_value'] ?? '1');
                    @endphp
                    <select name="allow_phone_orders" class="form-input">
                        <option value="1" {{ in_array($phoneOrdersAllowed, ['1', 'true', 'on', 'yes'], true) ? 'selected' : '' }}>
                            Enabled — Field workers can book direct / phone orders without a physical store visit
                        </option>
                        <option value="0" {{ in_array($phoneOrdersAllowed, ['0', 'false', 'off', 'no'], true) ? 'selected' : '' }}>
                            Disabled — Mandatory physical store visit (Check-in) required for all orders
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

    {{-- ── 5. Field Visit & Geofencing Rules ── --}}
    <div class="panel p-6 border-l-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-map-pin class="w-5 h-5 text-amber-600" />
                    Field Visit & Geofencing Rules
                </h2>
                <p class="text-caption mt-1">Configure physical distance limits for party/customer check-ins from the mobile app.</p>
            </div>
            <div class="text-xs text-amber-800 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200 font-semibold flex items-center gap-1.5">
                <x-lucide-shield-alert class="w-4 h-4 text-amber-600 shrink-0" />
                <span>Geofence Guard: Prevents remote or fake check-ins.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Geofence Check-In Distance Radius (Meters) <span class="text-rose-500">*</span></label>
                    <input type="number" name="geofence_radius_meters" min="50" max="5000" step="25"
                           value="{{ $settings['geofence_radius_meters']['setting_value'] ?? '300' }}"
                           required class="form-input" />
                    <p class="text-xs text-ink-500 mt-1">
                        Maximum distance (in meters) allowed between the rep's phone GPS and the customer's registered location to check in.
                        <br><span class="text-amber-700 font-medium">Recommended: 250m – 500m</span> to prevent false rejections due to mobile GPS drift in dense markets, indoor shops, or under tin roofs.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Geofencing Settings
                </x-button>
            </div>
        </form>
    </div>

    {{-- ── 6. Live Location Tracking Configuration ── --}}
    <div class="panel p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-ink-900 flex items-center gap-2">
                    <x-lucide-navigation class="w-5 h-5 text-blue-600" />
                    Live Location Tracking Configuration
                </h2>
                <p class="text-caption mt-1">Configure real-time GPS tracking settings for field workers during active work hours (Day Start to Day End).</p>
            </div>
            <div class="text-xs text-blue-800 bg-blue-50 px-3 py-2 rounded-lg border border-blue-200 font-semibold flex items-center gap-1.5">
                <x-lucide-radio class="w-4 h-4 text-blue-600 shrink-0" />
                <span>Background Tracking: Works even when app is minimized or screen is off.</span>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" @submit.prevent="submitForm($event)">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="form-label">Time Interval (Seconds) <span class="text-rose-500">*</span></label>
                    <select name="location_tracking_time_interval" class="form-input" required>
                        @php $timeInterval = $settings['location_tracking_time_interval']['setting_value'] ?? '60'; @endphp
                        <option value="30" {{ $timeInterval == '30' ? 'selected' : '' }}>30 seconds (High frequency - more battery usage)</option>
                        <option value="60" {{ $timeInterval == '60' ? 'selected' : '' }}>60 seconds (Balanced - Recommended)</option>
                        <option value="120" {{ $timeInterval == '120' ? 'selected' : '' }}>2 minutes (120 seconds)</option>
                        <option value="300" {{ $timeInterval == '300' ? 'selected' : '' }}>5 minutes (300 seconds - Battery saver)</option>
                        <option value="600" {{ $timeInterval == '600' ? 'selected' : '' }}>10 minutes (600 seconds - Minimal tracking)</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">How often to capture location when worker is stationary. Lower = more frequent = more battery drain.</p>
                </div>

                <div>
                    <label class="form-label">Distance Interval (Meters) <span class="text-rose-500">*</span></label>
                    <select name="location_tracking_distance_interval" class="form-input" required>
                        @php $distInterval = $settings['location_tracking_distance_interval']['setting_value'] ?? '50'; @endphp
                        <option value="1" {{ $distInterval == '1' ? 'selected' : '' }}>1 meter (Testing only - very high battery usage)</option>
                        <option value="10" {{ $distInterval == '10' ? 'selected' : '' }}>10 meters (High precision)</option>
                        <option value="50" {{ $distInterval == '50' ? 'selected' : '' }}>50 meters (Balanced - Recommended)</option>
                        <option value="100" {{ $distInterval == '100' ? 'selected' : '' }}>100 meters (Low frequency)</option>
                        <option value="500" {{ $distInterval == '500' ? 'selected' : '' }}>500 meters (Minimal - for long distance travel)</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">Capture location when worker moves this distance. Triggers BEFORE time interval if movement detected.</p>
                </div>

                <div>
                    <label class="form-label">GPS Accuracy <span class="text-rose-500">*</span></label>
                    <select name="location_tracking_accuracy" class="form-input" required>
                        @php $accuracy = $settings['location_tracking_accuracy']['setting_value'] ?? 'high'; @endphp
                        <option value="high" {{ $accuracy == 'high' ? 'selected' : '' }}>High Accuracy (Most precise, higher battery usage)</option>
                        <option value="balanced" {{ $accuracy == 'balanced' ? 'selected' : '' }}>Balanced (Good precision, moderate battery)</option>
                        <option value="low" {{ $accuracy == 'low' ? 'selected' : '' }}>Low Power (Less precise, saves battery)</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">GPS accuracy mode. High = best for route tracking but drains battery faster.</p>
                </div>

                <div>
                    <label class="form-label">Tracking Mode <span class="text-rose-500">*</span></label>
                    <select name="location_tracking_enabled" class="form-input" required>
                        @php $enabled = $settings['location_tracking_enabled']['setting_value'] ?? '1'; @endphp
                        <option value="1" {{ $enabled == '1' ? 'selected' : '' }}>Enabled - Track all field workers</option>
                        <option value="0" {{ $enabled == '0' ? 'selected' : '' }}>Disabled - No location tracking</option>
                    </select>
                    <p class="text-xs text-ink-500 mt-1">Master switch to enable/disable location tracking for all field workers.</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-bold text-blue-900 mb-2 flex items-center gap-2">
                    <x-lucide-info class="w-4 h-4 text-blue-600" />
                    How Location Tracking Works:
                </h3>
                <ul class="text-xs text-blue-800 space-y-1.5 ml-6 list-disc">
                    <li><strong>Time-based:</strong> Location captured every <span class="font-mono bg-blue-100 px-1 rounded">Time Interval</span> seconds (e.g., 60s) even if worker is stationary.</li>
                    <li><strong>Movement-based:</strong> Location captured immediately when worker moves <span class="font-mono bg-blue-100 px-1 rounded">Distance Interval</span> meters (e.g., 50m) - happens BEFORE time interval.</li>
                    <li><strong>OR Logic:</strong> Whichever happens FIRST triggers location capture (time OR distance).</li>
                    <li><strong>Background Service:</strong> Tracking continues even when app is minimized, screen is off, or worker is using other apps.</li>
                    <li><strong>Battery Impact:</strong> Lower intervals = more frequent updates = higher battery drain. Recommended: 60s time + 50m distance.</li>
                </ul>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-surface-border">
                <x-button type="submit" variant="primary">
                    <x-lucide-save class="w-4 h-4 inline mr-1" /> Save Location Tracking Settings
                </x-button>
            </div>
        </form>
    </div>

</div>

@endsection

@push('scripts')
<script>
function settingsFormHandler() {
    return {
        async submitForm(event) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = btn ? btn.innerHTML : '';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> Saving...
                `;
            }

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
                    window.toast(data.message || 'Settings saved successfully!', 'success');
                } else {
                    window.toast(data.message || 'Failed to save settings.', 'error');
                }
            } catch (e) {
                console.error(e);
                window.toast('Network error saving settings.', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        }
    };
}
</script>
@endpush
