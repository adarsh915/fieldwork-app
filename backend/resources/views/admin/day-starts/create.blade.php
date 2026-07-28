@extends('admin.layouts.app')

@section('title', 'Start My Day')
@section('breadcrumb', 'Admin / Day Start / Create')
@section('page_title', 'Start My Day')

@section('content')
    <section class="panel">
        <div class="panel-header">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Day Start / Tour Plan</h2>
                    <p class="mt-1 text-sm text-slate-500">Submit your tour plan for today. Location is captured automatically on submit.</p>
                </div>
                <a href="{{ route('admin.day-starts.index') }}" class="btn-secondary">Back to List</a>
            </div>
        </div>

        <form id="day-start-form" method="POST" action="{{ route('admin.day-starts.store') }}" enctype="multipart/form-data" class="p-5">
            @csrf
            <input type="hidden" name="start_lat" id="start_lat" value="{{ old('start_lat') }}">
            <input type="hidden" name="start_lng" id="start_lng" value="{{ old('start_lng') }}">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Date</label>
                        <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="form-input mt-2" required>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Purpose</label>
                        <select name="tour_purpose_id" id="tour_purpose_id" class="form-input mt-2" required>
                            <option value="">Select tour purpose</option>
                            @foreach ($tourPurposes as $purpose)
                                <option value="{{ $purpose->id }}" @selected(old('tour_purpose_id') == $purpose->id)>{{ $purpose->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Tour Type</label>
                        <select name="tour_type_id" id="tour_type_id" class="form-input mt-2" required>
                            <option value="">Select tour type</option>
                            @foreach ($tourTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('tour_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle Type</label>
                        <select name="vehicle_type_id" id="vehicle_type_id" class="form-input mt-2" required>
                            <option value="">Select vehicle type</option>
                            @foreach ($vehicleTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('vehicle_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="field-vehicle-no" class="hidden">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Vehicle No.</label>
                        <input type="text" name="vehicle_no" id="vehicle_no" value="{{ old('vehicle_no') }}" class="form-input mt-2" placeholder="GJ-XX-XXXX">
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Visit Place</label>
                        <input type="text" name="visit_place" value="{{ old('visit_place') }}" class="form-input mt-2" placeholder="Place to visit">
                    </div>

                    <div id="field-party" class="hidden">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Party</label>
                        <input type="text" id="party_search" class="form-input mt-2" placeholder="Search party...">
                        <select name="party_id" id="party_id" class="form-input mt-2">
                            <option value="">Select party</option>
                            @forelse ($customers as $customer)
                                <option value="{{ $customer->id }}" data-name="{{ strtolower($customer->name) }}" @selected(old('party_id') == $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @empty
                                <option value="" disabled>No parties assigned to you</option>
                            @endforelse
                        </select>
                        @if ($customers->isEmpty())
                            <p class="mt-2 text-xs text-amber-700">
                                Ask your admin to assign parties under Employees → Edit Employee → Assigned Parties.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <div id="field-opening-km" class="hidden">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening K.M.</label>
                        <input type="number" step="0.01" min="0" name="opening_km" id="opening_km" value="{{ old('opening_km') }}" class="form-input mt-2" placeholder="0.00">
                    </div>

                    <div id="field-opening-km-image" class="hidden">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Opening KM Photo</label>
                        <input type="file" name="opening_km_image" id="opening_km_image" accept="image/*" class="form-input mt-2">
                        <div class="mt-3 hidden" id="image-preview-wrap">
                            <img id="image-preview" src="" alt="Preview" class="max-h-48 rounded-lg border border-slate-200">
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-800">Location capture</p>
                        <p class="mt-1">Your device location will be captured automatically when you submit this form.</p>
                        <p class="mt-2 text-xs text-slate-500" id="geo-status">Waiting for submit...</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" id="submit-btn" class="btn-primary flex-1 py-3">Submit Tour Plan</button>
                        <a href="{{ route('admin.day-starts.index') }}" class="btn-secondary py-3">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
    (function () {
        const purposeFlags = @json($purposeFlags);
        const tourTypeFlags = @json($tourTypeFlags);
        const vehicleTypeFlags = @json($vehicleTypeFlags);

        const purposeSelect = document.getElementById('tour_purpose_id');
        const tourTypeSelect = document.getElementById('tour_type_id');
        const vehicleTypeSelect = document.getElementById('vehicle_type_id');

        const fieldParty = document.getElementById('field-party');
        const fieldVehicleNo = document.getElementById('field-vehicle-no');
        const fieldOpeningKm = document.getElementById('field-opening-km');
        const fieldOpeningKmImage = document.getElementById('field-opening-km-image');

        const vehicleNoInput = document.getElementById('vehicle_no');
        const partySelect = document.getElementById('party_id');
        const openingKmInput = document.getElementById('opening_km');
        const openingKmImageInput = document.getElementById('opening_km_image');

        function toggleField(field, show, requiredInputs = []) {
            field.classList.toggle('hidden', !show);
            requiredInputs.forEach(function (input) {
                if (show) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    if (input.type === 'file') {
                        input.value = '';
                    } else {
                        input.value = '';
                    }
                }
            });
        }

        function updateConditionalFields() {
            const purposeId = purposeSelect.value;
            const tourTypeId = tourTypeSelect.value;
            const vehicleTypeId = vehicleTypeSelect.value;

            const purpose = purposeFlags[purposeId] || {};
            const tourType = tourTypeFlags[tourTypeId] || {};
            const vehicleType = vehicleTypeFlags[vehicleTypeId] || {};

            const requiresParty = Boolean(purpose.requires_party);
            let requiresVehicleNo = Boolean(vehicleType.requires_vehicle_no);
            let requiresKmAndPhoto = Boolean(tourType.requires_vehicle) && Boolean(vehicleType.requires_vehicle_no);

            const purposeOption = purposeSelect.options[purposeSelect.selectedIndex];
            if (purposeOption && purposeOption.text === 'Work From Home') {
                requiresKmAndPhoto = false;
                requiresVehicleNo = false;
            }

            toggleField(fieldParty, requiresParty, requiresParty ? [partySelect] : []);
            toggleField(fieldVehicleNo, requiresVehicleNo, requiresVehicleNo ? [vehicleNoInput] : []);
            toggleField(fieldOpeningKm, requiresKmAndPhoto, requiresKmAndPhoto ? [openingKmInput] : []);
            toggleField(fieldOpeningKmImage, requiresKmAndPhoto, requiresKmAndPhoto ? [openingKmImageInput] : []);
        }

        purposeSelect.addEventListener('change', updateConditionalFields);
        tourTypeSelect.addEventListener('change', updateConditionalFields);
        vehicleTypeSelect.addEventListener('change', updateConditionalFields);
        updateConditionalFields();

        const partySearch = document.getElementById('party_search');
        partySearch.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            Array.from(partySelect.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }
                const name = option.getAttribute('data-name') || '';
                option.hidden = term !== '' && !name.includes(term);
            });
        });

        const imagePreview = document.getElementById('image-preview');
        const imagePreviewWrap = document.getElementById('image-preview-wrap');
        openingKmImageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) {
                imagePreviewWrap.classList.add('hidden');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreviewWrap.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });

        const form = document.getElementById('day-start-form');
        const geoStatus = document.getElementById('geo-status');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            submitBtn.disabled = true;
            submitBtn.textContent = 'Capturing location...';
            geoStatus.textContent = 'Requesting location permission...';

            if (!navigator.geolocation) {
                geoStatus.textContent = 'Geolocation not supported. Submitting without location.';
                form.submit();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    document.getElementById('start_lat').value = position.coords.latitude;
                    document.getElementById('start_lng').value = position.coords.longitude;
                    geoStatus.textContent = 'Location captured. Submitting...';
                    form.submit();
                },
                function () {
                    geoStatus.textContent = 'Location unavailable. Submitting without coordinates.';
                    form.submit();
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    })();
</script>
@endpush
