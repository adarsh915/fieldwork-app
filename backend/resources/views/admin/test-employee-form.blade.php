@extends('admin.layouts.app')

@section('title', 'Test Employee Form')
@section('breadcrumb', 'Admin / Debug / Test Form')
@section('page_title', 'Test Employee Creation Form')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Instructions --}}
        <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
            <h3 class="font-bold text-blue-900">🧪 Debug Instructions:</h3>
            <ol class="mt-2 list-inside list-decimal space-y-1 text-sm text-blue-800">
                <li>Fill out the form below</li>
                <li>Click "Test Submit"</li>
                <li>Check if you see success message</li>
                <li>Check database by running: <code class="rounded bg-blue-100 px-1">php show_all_employees.php</code></li>
                <li>Check Laravel log: <code class="rounded bg-blue-100 px-1">storage/logs/laravel.log</code></li>
            </ol>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('status'))
            <div class="rounded-lg border-2 border-green-500 bg-green-50 p-4">
                <p class="font-bold text-green-800">✅ {{ session('status') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border-2 border-red-500 bg-red-50 p-4">
                <p class="font-bold text-red-800">❌ {{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border-2 border-red-500 bg-red-50 p-4">
                <p class="font-bold text-red-800">⚠️ Validation Errors:</p>
                <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Test Form --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="font-semibold text-slate-950">Test Employee Form</h2>
                <p class="mt-1 text-sm text-slate-500">This form will help us debug the issue</p>
            </div>

            <form method="POST" action="{{ route('admin.employees.store') }}" class="p-6" onsubmit="console.log('Form submitted!'); return true;">
                @csrf

                <div class="space-y-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            value="{{ old('name', 'Debug Test Employee') }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            name="email" 
                            value="{{ old('email', 'debug.test.' . rand(100, 999) . '@example.com') }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 @error('email') border-red-500 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            value="password123"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 @error('password') border-red-500 @enderror"
                            required
                            minlength="8"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Pre-filled with: password123</p>
                        @enderror
                    </div>

                    {{-- Employee ID --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Employee ID
                        </label>
                        <input 
                            type="text" 
                            name="employee_id" 
                            value="{{ old('employee_id', 'TEST' . rand(1000, 9999)) }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"
                        >
                    </div>

                    {{-- Position --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Position
                        </label>
                        <input 
                            type="text" 
                            name="position" 
                            value="{{ old('position', 'Test Position') }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"
                        >
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Department
                        </label>
                        <input 
                            type="text" 
                            name="department" 
                            value="{{ old('department', 'Testing') }}"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2"
                        >
                    </div>

                    {{-- Active Status --}}
                    <div>
                        <label class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                name="is_active" 
                                value="1"
                                checked
                                class="rounded border-slate-300"
                            >
                            <span class="text-sm font-medium text-slate-700">Active Employee</span>
                        </label>
                    </div>

                    {{-- Roles --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Assign Roles
                        </label>
                        <div class="space-y-2">
                            @foreach(\Spatie\Permission\Models\Role::all() as $role)
                                <label class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        name="roles[]" 
                                        value="{{ $role->name }}"
                                        {{ $role->name === 'Employee' ? 'checked' : '' }}
                                        class="rounded border-slate-300"
                                    >
                                    <span class="text-sm text-slate-700">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="mt-6 flex gap-3 border-t border-slate-200 pt-6">
                    <button 
                        type="submit" 
                        class="rounded-lg bg-blue-600 px-6 py-2 font-bold text-white hover:bg-blue-700"
                        onclick="console.log('Submit button clicked'); alert('Form will be submitted now');"
                    >
                        🧪 Test Submit
                    </button>
                    <a 
                        href="{{ route('admin.employees.index') }}" 
                        class="rounded-lg border-2 border-slate-300 bg-white px-6 py-2 font-bold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>
                </div>
            </form>

            {{-- Debug Info --}}
            <div class="border-t border-slate-200 bg-slate-50 p-4">
                <details class="text-sm">
                    <summary class="cursor-pointer font-bold text-slate-700">🔧 Technical Details</summary>
                    <div class="mt-3 space-y-1 text-slate-600">
                        <p><strong>Form Action:</strong> {{ route('admin.employees.store') }}</p>
                        <p><strong>Form Method:</strong> POST</p>
                        <p><strong>CSRF Token:</strong> {{ csrf_token() }}</p>
                        <p><strong>Controller:</strong> App\Http\Controllers\Admin\EmployeeWebController@store</p>
                        <p><strong>Model:</strong> App\Models\User</p>
                        <p><strong>Table:</strong> users</p>
                        <p><strong>Current User:</strong> {{ auth()->user()->email }}</p>
                        <p><strong>Has Permission:</strong> {{ auth()->user()->can('employees.create') ? 'Yes' : 'No' }}</p>
                    </div>
                </details>
            </div>
        </section>

        {{-- JavaScript Debug --}}
        <script>
            console.log('Test form page loaded');
            console.log('Form action:', '{{ route('admin.employees.store') }}');
            console.log('CSRF token present:', document.querySelector('input[name="_token"]') !== null);
            
            // Monitor form submission
            document.querySelector('form').addEventListener('submit', function(e) {
                console.log('=== FORM SUBMISSION DEBUG ===');
                console.log('Form is submitting...');
                
                const formData = new FormData(this);
                console.log('Form data:');
                for (let [key, value] of formData.entries()) {
                    if (key === 'password') {
                        console.log(key + ': [HIDDEN]');
                    } else {
                        console.log(key + ':', value);
                    }
                }
                
                console.log('This form will now submit to:', this.action);
            });
        </script>
    </div>
@endsection
