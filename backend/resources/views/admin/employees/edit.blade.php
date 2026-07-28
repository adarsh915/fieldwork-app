@extends('admin.layouts.app')

@section('title', 'Edit Employee')
@section('breadcrumb', 'Admin / Employees / Edit')
@section('page_title', 'Edit Employee')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/employee-management.css') }}">
@endpush

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            ← Back to Employees
        </a>
    </div>

    <section class="panel max-w-4xl">
        <div class="panel-header">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Edit Employee Information</h2>
                    <p class="mt-1 text-sm text-slate-500">Update employee details and role assignments.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($employee->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-danger">Inactive</span>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.employees.update', $employee->id) }}" class="p-6">
            @csrf
            @method('PUT')

            {{-- Personal Information --}}
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-bold text-slate-950">Personal Information</h3>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $employee->employee_id) }}" class="form-input @error('employee_id') border-red-500 @enderror" placeholder="e.g., EMP001">
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-input @error('email') border-red-500 @enderror" required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-input @error('phone') border-red-500 @enderror" placeholder="+91 1234567890">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" class="form-input @error('date_of_birth') border-red-500 @enderror">
                        @error('date_of_birth')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input @error('password') border-red-500 @enderror" placeholder="Leave blank to keep current password">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-slate-500">Only fill this if you want to change the password</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-input @error('address') border-red-500 @enderror">{{ old('address', $employee->address) }}</textarea>
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
                        <input type="text" name="department" value="{{ old('department', $employee->department) }}" class="form-input @error('department') border-red-500 @enderror" placeholder="e.g., IT, Sales, Operations">
                        @error('department')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Position</label>
                        <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="form-input @error('position') border-red-500 @enderror" placeholder="e.g., Manager, Developer">
                        @error('position')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" class="form-input @error('hire_date') border-red-500 @enderror">
                        @error('hire_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Salary (₹)</label>
                        <input type="number" name="salary" value="{{ old('salary', $employee->salary) }}" class="form-input @error('salary') border-red-500 @enderror" step="0.01" min="0" placeholder="50000">
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
                                    @checked(in_array($role->name, old('roles', $employee->roles->pluck('name')->toArray())))
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
                            @checked(old('is_active', $employee->is_active))
                            class="h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        >
                        <span class="font-medium text-slate-700">Active Employee</span>
                    </label>
                    <p class="ml-8 mt-1 text-sm text-slate-500">Uncheck to deactivate this employee account</p>
                </div>
            </div>

            @include('admin.employees.partials.party-assignment')

            {{-- Additional Info --}}
            <div class="mb-8 rounded-lg bg-slate-50 p-4">
                <h4 class="mb-2 text-sm font-bold text-slate-700">Account Information</h4>
                <div class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                    <div>
                        <span class="text-slate-500">Created:</span>
                        <span class="font-medium text-slate-900">{{ $employee->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Last Updated:</span>
                        <span class="font-medium text-slate-900">{{ $employee->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                <button type="submit" class="btn-primary">
                    Update Employee
                </button>
                <a href="{{ route('admin.employees.index') }}" class="btn-secondary">
                    Cancel
                </a>
                
                @if(auth()->id() !== $employee->id)
                    <form method="POST" action="{{ route('admin.employees.destroy', $employee->id) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')" 
                          class="ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md bg-red-50 px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-100">
                            Delete Employee
                        </button>
                    </form>
                @endif
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/employee-management.js') }}"></script>
@endpush
