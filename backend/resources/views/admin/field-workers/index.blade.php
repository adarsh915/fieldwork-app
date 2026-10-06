@extends('admin.layouts.app')

@section('title', 'Field Worker Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6">
    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <x-lucide-users class="w-8 h-8 text-blue-600" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Field Worker Management</h1>
                        <p class="text-slate-600 mt-1">Manage and monitor all field workers</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-slate-200">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Email, ID..."
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Department Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Department</label>
                        <select name="department" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <!-- Apply Button -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center justify-center gap-2">
                            <x-lucide-search class="w-4 h-4" />
                            Apply Filter
                        </button>
                        <a href="{{ route('admin.field-workers.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Field Workers Grid -->
        @if($fieldWorkers->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                @foreach($fieldWorkers as $fw)
                    <div class="bg-white rounded-lg shadow-md border border-slate-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-300 to-blue-700 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                                    {{ strtoupper(substr($fw->name, 0, 1)) }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-white text-lg">{{ $fw->name }} ({{ $fw->role_name }})</h3>
                                    <div class="mt-2">
                                        <span class="inline-block px-2 py-1 rounded-full text-xs font-bold @class([
                                            'bg-green-100 text-green-700' => $fw->is_active,
                                            'bg-slate-100 text-slate-700' => !$fw->is_active,
                                        ])">
                                            {{ $fw->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="px-6 py-4 border-b border-slate-200">
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-lucide-mail class="w-4 h-4" />
                                    {{ $fw->email }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-lucide-phone class="w-4 h-4" />
                                    {{ $fw->phone ?? 'N/A' }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-lucide-briefcase class="w-4 h-4" />
                                    {{ $fw->position ?? 'N/A' }}
                                </div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-white rounded p-3 text-center border border-slate-200">
                                    <p class="text-2xl font-bold text-blue-600">{{ $fw->stats['total_parties'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1">Parties</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center border border-slate-200">
                                    <p class="text-2xl font-bold text-green-600">{{ $fw->stats['total_orders'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1">Orders</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center border border-slate-200">
                                    <p class="text-2xl font-bold text-purple-600">{{ $fw->stats['total_visits'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1">Visits</p>
                                </div>
                                <div class="bg-white rounded p-3 text-center border border-slate-200">
                                    <p class="text-2xl font-bold text-orange-600">{{ $fw->stats['pending_orders'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1">Pending</p>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Info -->
                        <div class="px-6 py-4 border-b border-slate-200">
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">Total Revenue</p>
                                    <p class="text-xl font-bold text-slate-900">₹ {{ number_format($fw->stats['total_revenue'], 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-600 mb-1">Avg Order Value</p>
                                    <p class="text-lg font-bold text-slate-900">₹ {{ number_format($fw->stats['avg_order_value'], 0) }}</p>
                                </div>
                                <div class="pt-2 border-t border-slate-200">
                                    <p class="text-xs text-slate-600 mb-1">Pending Payments</p>
                                    <p class="text-lg font-bold text-red-600">₹ {{ number_format($fw->stats['pending_payments'], 0) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="px-6 py-4 space-y-2">
                            <a href="{{ route('admin.field-workers.metrics', $fw->id) }}" class="w-full block text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-sm">
                                View Metrics
                            </a>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('admin.field-workers.visits', $fw->id) }}" class="text-center px-3 py-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition font-semibold text-sm">
                                    Visits
                                </a>
                                <a href="{{ route('admin.field-workers.transactions', $fw->id) }}" class="text-center px-3 py-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition font-semibold text-sm">
                                    Transactions
                                </a>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button onclick="editFieldWorker({{ $fw->id }})" class="text-center px-3 py-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition font-semibold text-sm">
                                    Edit
                                </button>
                                <a href="{{ route('admin.field-workers.export', $fw->id) }}" class="text-center px-3 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition font-semibold text-sm">
                                    Export
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mb-8">
                {{ $fieldWorkers->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg border border-slate-200 p-12 text-center">
                <x-lucide-inbox class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <p class="text-slate-600 mb-2">No field workers found</p>
                <p class="text-sm text-slate-500">Try adjusting your search or filter criteria</p>
            </div>
        @endif
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full shadow-xl">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-900">Edit Field Worker</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <x-lucide-x class="w-6 h-6" />
            </button>
        </div>
        <form id="editForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Phone</label>
                <input type="tel" name="phone" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                <select name="is_active" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 bg-slate-300 text-slate-700 rounded-lg hover:bg-slate-400 transition font-semibold">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentFieldWorkerId = null;

    function editFieldWorker(id) {
        currentFieldWorkerId = id;
        // Fetch field worker data and populate form
        fetch(`/admin/field-workers/${id}/edit`)
            .then(r => r.text())
            .then(html => {
                // Parse and populate form
                document.getElementById('editModal').classList.remove('hidden');
            });
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    document.getElementById('editForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const response = await fetch(`/admin/field-workers/${currentFieldWorkerId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });
        if (response.ok) {
            alert('Field worker updated successfully');
            location.reload();
        }
    });
</script>
@endsection
