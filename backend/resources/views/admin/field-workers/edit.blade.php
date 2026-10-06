@extends('admin.layouts.app')

@section('title', 'Edit - ' . $fieldWorker->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-2xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.field-workers.index') }}" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-4">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Field Workers
            </a>
            <h1 class="text-3xl font-bold text-slate-900">Edit Field Worker</h1>
            <p class="text-slate-600 mt-2">{{ $fieldWorker->name }} ({{ $fieldWorker->role_name }})</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.field-workers.update', $fieldWorker->id) }}" class="bg-white rounded-lg shadow-md p-8 border border-slate-200">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $fieldWorker->name) }}" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter full name">
                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Email -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email *</label>
                <input type="email" name="email" value="{{ old('email', $fieldWorker->email) }}" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter email address">
                @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Phone -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Phone</label>
                <input type="tel" name="phone" value="{{ old('phone', $fieldWorker->phone) }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter phone number">
                @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Address -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Address</label>
                <textarea name="address" rows="3"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter address">{{ old('address', $fieldWorker->address) }}</textarea>
                @error('address') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Department -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Department</label>
                <input type="text" name="department" value="{{ old('department', $fieldWorker->department) }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter department">
                @error('department') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Position -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Position</label>
                <input type="text" name="position" value="{{ old('position', $fieldWorker->position) }}"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter position">
                @error('position') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Status -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status *</label>
                <select name="is_active" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1" @selected(old('is_active', $fieldWorker->is_active) == 1)>Active</option>
                    <option value="0" @selected(old('is_active', $fieldWorker->is_active) == 0)>Inactive</option>
                </select>
                @error('is_active') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Buttons -->
            <div class="flex gap-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center justify-center gap-2">
                    <x-lucide-save class="w-4 h-4" />
                    Save Changes
                </button>
                <a href="{{ route('admin.field-workers.index') }}" class="flex-1 px-6 py-3 bg-slate-300 text-slate-700 rounded-lg hover:bg-slate-400 transition font-semibold text-center">
                    Cancel
                </a>
            </div>
        </form>

        <!-- Info Box -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex gap-3">
                <x-lucide-info class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="font-semibold text-blue-900">Information</p>
                    <p class="text-sm text-blue-800 mt-1">
                        Employee ID and other system-generated information cannot be edited. Contact administrator for changes.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
