@extends('admin.layouts.app')

@section('title', 'Create Employee')
@section('breadcrumb', 'Admin / Employees / Create')
@section('page_title', 'Create New Employee')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-management.css') }}">
@endpush

@section('content')
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← View All Employees
        </a>
    </div>

    {{-- Show General Error --}}
    @if($errors->has('error'))
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <span class="text-red-600">⚠️</span>
                <div class="flex-1">
                    <p class="font-semibold text-red-800">Error Creating Employee</p>
                    <p class="mt-1 text-sm text-red-700">{{ $errors->first('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Show Validation Errors Summary --}}
    @if($errors->any() && !$errors->has('error'))
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <span class="text-red-600">⚠️</span>
                <div class="flex-1">
                    <p class="font-semibold text-red-800">Please fix the following errors:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <section class="panel max-w-4xl">
        <div class="panel-header">
            <h2 class="font-semibold text-slate-950">Employee Information</h2>
            <p class="mt-1 text-sm text-slate-500">Fill in the details to create a new employee account.</p>
        </div>

        <form method="POST" action="{{ route('admin.employees.store') }}" class="p-6">
            @csrf

            {{-- Personal Information --}}
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-bold text-slate-950">Personal Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input @error('name') border-red-500 ring-2 ring-red-200 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-input @error('employee_id') border-red-500 ring-2 ring-red-200 @enderror" placeholder="e.g., EMP001">
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Optional: Unique employee identifier</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-input @error('email') border-red-500 ring-2 ring-red-200 @enderror" required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-input @error('phone') border-red-500 ring-2 ring-red-200 @enderror" placeholder="+91 1234567890">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-input @error('date_of_birth') border-red-500 ring-2 ring-red-200 @enderror" max="{{ date('Y-m-d') }}">
                        @error('date_of_birth')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" class="form-input @error('password') border-red-500 ring-2 ring-red-200 @enderror" required minlength="8">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Minimum 8 characters required</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-input @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Employment Information --}}
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-bold text-slate-950">Employment Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label">Department</label>
                        <input type="text" name="department" value="{{ old('department') }}" class="form-input @error('department') border-red-500 @enderror" placeholder="e.g., IT, Sales, Operations">
                        @error('department')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Position</label>
                        <input type="text" name="position" value="{{ old('position') }}" class="form-input @error('position') border-red-500 @enderror" placeholder="e.g., Manager, Developer">
                        @error('position')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" value="{{ old('hire_date') }}" class="form-input @error('hire_date') border-red-500 @enderror">
                        @error('hire_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Salary (₹)</label>
                        <input type="number" name="salary" value="{{ old('salary') }}" class="form-input @error('salary') border-red-500 @enderror" step="0.01" min="0" placeholder="50000">
                        @error('salary')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Roles & Status --}}
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-bold text-slate-950">Roles & Status</h3>
                
                <div class="mb-5">
                    <label class="form-label">Assign Roles</label>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="{{ $role->name }}"
                                    @checked(in_array($role->name, old('roles', [])))
                                    class="rounded border-slate-300 text-slate-950 focus:ring-slate-300"
                                >
                                <span>{{ ucfirst($role->name) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', true))
                            class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        >
                        <span class="font-medium text-slate-700">Active Employee</span>
                    </label>
                    <p class="ml-8 mt-1 text-sm text-slate-500">Uncheck to create an inactive employee account</p>
                </div>
            </div>

            @include('admin.employees.partials.party-assignment')

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">
                    Create Employee
                </button>
                <a href="{{ route('admin.employees.index') }}" class="btn-secondary">
                    Cancel
                </a>
            </div>
        </form>

        {{-- Debug Info (Remove in production) --}}
        @if(config('app.debug'))
            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                <details class="text-sm">
                    <summary class="cursor-pointer font-semibold text-slate-600">🔧 Debug Info (DEV only)</summary>
                    <div class="mt-3 space-y-2 text-slate-600">
                        <p><strong>Form Action:</strong> {{ route('admin.employees.store') }}</p>
                        <p><strong>Method:</strong> POST</p>
                        <p><strong>CSRF:</strong> {{ csrf_token() ? '✅ Present' : '❌ Missing' }}</p>
                        <p><strong>Old Input:</strong> {{ old() ? json_encode(array_keys(old())) : 'None' }}</p>
                        <p><strong>Validation Errors:</strong> {{ $errors->count() }}</p>
                    </div>
                </details>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/employee-management.js') }}"></script>
@endpush
