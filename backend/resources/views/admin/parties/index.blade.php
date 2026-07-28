@extends('admin.layouts.app')
@section('title', 'Party Master')
@section('breadcrumb', 'ADMIN / PARTIES')
@section('page_title', 'Party Master')

@section('content')

    @if(session('status'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-success-bg px-4 py-3">
            <x-lucide-check-circle class="w-5 h-5 text-success-text shrink-0" />
            <p class="text-sm font-medium text-success-text">{{ session('status') }}</p>
        </div>
    @endif

    <div class="panel">
        {{-- Header --}}
        <div class="panel-header">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-section-title">Manage Parties</h2>
                    <p class="text-caption mt-1">View, search, filter, and assign parties to field workers.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('parties.assign')
                        <x-button x-data @click="$dispatch('open-bulk-assign-modal')" variant="secondary"
                            class="border-brand-200 text-brand-700 hover:bg-brand-50">
                            <x-lucide-users class="w-4 h-4" />
                            Bulk Assign
                        </x-button>
                    @endcan
                    @can('customers.create')
                        <x-button href="{{ route('admin.parties.create') }}" variant="primary">
                            <x-lucide-plus class="w-4 h-4" />
                            Add Party
                        </x-button>
                    @endcan
                </div>
            </div>

            {{-- Filters --}}
            <div x-data="{
                                                                                search: '',
                                                                                employeeId: '',
                                                                                partyType: '',
                                                                                category: '',
                                                                                status: '',
                                                                                filtersVisible: false
                                                                            }" class="mt-4">
                <div class="flex flex-wrap gap-3 items-end">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[200px]">
                        <x-lucide-search
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400 pointer-events-none" />
                        <input x-model="search" @input.debounce.500ms="window.partyTable.search(search).draw()"
                            class="form-input pl-9" placeholder="Search name, code, mobile, email, city…">
                    </div>

                    {{-- Toggle Filters Button --}}
                    <x-button type="button" variant="secondary" @click="filtersVisible = !filtersVisible">
                        <x-lucide-filter class="w-4 h-4" />
                        <span x-text="filtersVisible ? 'Hide Filters' : 'Show Filters'"></span>
                    </x-button>

                    {{-- Clear All --}}
                    <x-button type="button" variant="secondary"
                        @click="search=''; employeeId=''; partyType=''; category=''; status=''; window.partyTable.search('').draw()"
                        x-show="search || employeeId || partyType || category || status">
                        <x-lucide-x class="w-4 h-4" />
                        Clear
                    </x-button>
                </div>

                {{-- Advanced Filters --}}
                <div x-show="filtersVisible" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Assigned Employee</label>
                        <select x-model="employeeId" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Employees</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Party Type</label>
                        <select x-model="partyType" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Types</option>
                            @foreach ($partyTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Category</label>
                        <select x-model="category" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-ink-600 mb-1 block">Status</label>
                        <select x-model="status" @change="window.partyTable.draw()" class="form-input">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-wrap">
            <table id="parties-table" class="data-table">
                <thead>
                    <tr>
                        <th class="w-10">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                        </th>
                        <th>Party Info</th>
                        <th>Contact</th>
                        <th>Classification</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- DataTables will populate this --}}
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bulk Assignment Modal --}}
    <div x-data="{
                                                                        open: false,
                                                                        selectedParties: [],
                                                                        selectedEmployee: '',
                                                                        loading: false,
                                                                        init() {
                                                                            this.$watch('open', value => {
                                                                                if (!value) {
                                                                                    this.selectedEmployee = '';
                                                                                }
                                                                            });
                                                                            window.addEventListener('open-bulk-assign-modal', () => {
                                                                                this.selectedParties = window.getSelectedPartyIds();
                                                                                if (this.selectedParties.length === 0) {
                                                                                    alert('Please select at least one party to assign.');
                                                                                    return;
                                                                                }
                                                                                this.open = true;
                                                                            });
                                                                        },
                                                                        async assignParties() {
                                                                            if (!this.selectedEmployee) {
                                                                                alert('Please select an employee.');
                                                                                return;
                                                                            }

                                                                            this.loading = true;
                                                                            try {
                                                                                const response = await fetch('{{ route('admin.parties.bulk-assign') }}', {
                                                                                    method: 'POST',
                                                                                    headers: {
                                                                                        'Content-Type': 'application/json',
                                                                                        'Accept': 'application/json',
                                                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                                                    },
                                                                                    body: JSON.stringify({
                                                                                        party_ids: this.selectedParties,
                                                                                        employee_id: this.selectedEmployee
                                                                                    })
                                                                                });

                                                                                const data = await response.json();

                                                                                if (data.success) {
                                                                                    window.partyTable.draw(false);
                                                                                    this.open = false;
                                                                                    alert(data.message);
                                                                                    document.getElementById('select-all').checked = false;
                                                                                } else {
                                                                                    alert(data.message || 'Assignment failed.');
                                                                                }
                                                                            } catch (error) {
                                                                                alert('An error occurred. Please try again.');
                                                                            } finally {
                                                                                this.loading = false;
                                                                            }
                                                                        }
                                                                    }" x-show="open" x-cloak
        class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="open = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Backdrop --}}
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false"
                class="fixed inset-0 bg-black bg-opacity-50"></div>

            {{-- Modal --}}
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-ink-900">Bulk Assign Parties</h3>
                    <button @click="open = false" class="text-ink-400 hover:text-ink-600">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-sm text-ink-600 mb-4">
                    Assign <span x-text="selectedParties.length" class="font-semibold"></span> selected parties to a field
                    worker.
                </p>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-ink-900 mb-2">Select Field Worker</label>
                    <select x-model="selectedEmployee" class="form-input" x-bind:disabled="loading">
                        <option value="">Choose an employee...</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" @click="open = false" x-bind:disabled="loading" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="button" @click="assignParties" x-bind:disabled="loading" class="btn btn-primary">
                        <span x-show="!loading">Assign Parties</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Assigning...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize DataTable
            window.partyTable = new DataTable('#parties-table', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.parties.data') }}',
                    data: function (d) {
                        const filters = Alpine.$data(document.querySelector('[x-data]'));
                        d.search = filters.search;
                        d.employee_id = filters.employeeId;
                        d.party_type = filters.partyType;
                        d.category = filters.category;
                        d.status = filters.status;
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return `<input type="checkbox" class="party-checkbox rounded border-gray-300" value="${row.party_info.id}">`;
                        }
                    },
                    {
                        data: 'party_info',
                        render: function (data) {
                            return `
                                                                                        <div class="text-body-emphasis">${data.name}</div>
                                                                                        <div class="text-caption">Code: ${data.party_code} • ${data.city}</div>
                                                                                    `;
                        }
                    },
                    {
                        data: 'contact',
                        render: function (data) {
                            return `
                                                                                        <div class="text-body">${data.mobile}</div>
                                                                                        <div class="text-caption">${data.email}</div>
                                                                                    `;
                        }
                    },
                    {
                        data: 'classification',
                        render: function (data) {
                            return `
                                                                                        <div class="text-body">${data.type}</div>
                                                                                        <div class="text-caption">Category: ${data.category}</div>
                                                                                    `;
                        }
                    },
                    {
                        data: 'assignment',
                        render: function (data) {
                            if (data.employee_name) {
                                return `
                                                                                            <div class="text-body-emphasis">${data.employee_name}</div>
                                                                                            <div class="text-caption">${data.employee_id} • ${data.assigned_at}</div>
                                                                                        `;
                            }
                            return '<span class="text-caption italic">Not assigned</span>';
                        }
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            if (data === 'active') {
                                return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success-text">Active</span>';
                            }
                            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>';
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-right',
                        render: function (data) {
                            return `
                                                                                        <div class="relative inline-block" x-data="{ open: false }">
                                                                                            <button @click="open = !open" @click.stop class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors">
                                                                                                <svg class="w-4 h-4 text-ink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                                                                </svg>
                                                                                            </button>
                                                                                            <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-44 bg-white border border-surface-border rounded-xl shadow-lg z-20 py-1 top-full" style="display: none;">
                                                                                                <a href="${data.view}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                                                    View Profile
                                                                                                </a>
                                                                                                ${data.can_edit ? `
                                                                                                <a href="${data.edit}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                                                                    Edit Details
                                                                                                </a>
                                                                                                ` : ''}
                                                                                                <a href="${data.history}" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-900 hover:bg-surface-muted">
                                                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                                                    View History
                                                                                                </a>
                                                                                                ${data.can_delete ? `
                                                                                                <div class="my-1 border-t border-surface-border"></div>
                                                                                                <form method="POST" action="${data.delete}" onsubmit="return confirm('Delete this party?')">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-sm text-danger-text hover:bg-danger-bg">
                                                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                                                        Delete Party
                                                                                                    </button>
                                                                                                </form>
                                                                                                ` : ''}
                                                                                            </div>
                                                                                        </div>
                                                                                    `;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                dom: 'rt<"flex items-center justify-between px-6 py-3 border-t border-surface-border"<"text-caption text-ink-400"i><""p>>'
            });

            // Select all checkbox
            document.getElementById('select-all').addEventListener('change', function () {
                document.querySelectorAll('.party-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                });
            });

            // Helper function to get selected party IDs
            window.getSelectedPartyIds = function () {
                return Array.from(document.querySelectorAll('.party-checkbox:checked')).map(cb => parseInt(cb.value));
            };
        });
    </script>
@endpush