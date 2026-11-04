{{--
    Route Definitions for JavaScript
    This file makes Laravel routes available to JavaScript in a safe way
    Usage in JavaScript: route.get('admin.payments.confirm', { id: 123 })
--}}

<script>
    // Wait for route helper to be loaded
    document.addEventListener('DOMContentLoaded', function() {
        if (window.route) {
            window.route.register({
                // Admin Panel Routes
                'admin.dashboard': '{{ route('admin.dashboard') }}',
                
                // Expense Routes
                'admin.expenses.index': '{{ route('admin.expenses.index') }}',
                'admin.expenses.data': '{{ route('admin.expenses.data') }}',
                'admin.expenses.show': '{{ route('admin.expenses.show', ':id') }}',
                'admin.expenses.temp-approve': '{{ route('admin.expenses.temp-approve', ':id') }}',
                'admin.expenses.final-approve': '{{ route('admin.expenses.final-approve', ':id') }}',
                'admin.expenses.reject': '{{ route('admin.expenses.reject', ':id') }}',
                
                // Payment Routes
                'admin.payments.index': '{{ route('admin.payments.index') }}',
                'admin.payments.data': '{{ route('admin.payments.data') }}',
                'admin.payments.confirm': '{{ route('admin.payments.confirm', ':id') }}',
                'admin.payments.reject': '{{ route('admin.payments.reject', ':id') }}',
                
                // Leave Routes
                'admin.leaves.index': '{{ route('admin.leaves.index') }}',
                'admin.leaves.data': '{{ route('admin.leaves.data') }}',
                'admin.leaves.show': '{{ route('admin.leaves.show', ':id') }}',
                'admin.leaves.approve': '{{ route('admin.leaves.approve', ':id') }}',
                'admin.leaves.reject': '{{ route('admin.leaves.reject', ':id') }}',
                
                // Day Start / Tour Plan Routes
                'admin.day-starts.index': '{{ route('admin.day-starts.index') }}',
                'admin.day-starts.data': '{{ route('admin.day-starts.data') }}',
                'admin.day-starts.show': '{{ route('admin.day-starts.show', ':id') }}',
                'admin.day-starts.approve': '{{ route('admin.day-starts.approve', ':id') }}',
                'admin.day-starts.reject': '{{ route('admin.day-starts.reject', ':id') }}',
                
                // Party / Customer Routes
                'admin.parties.index': '{{ route('admin.parties.index') }}',
                'admin.parties.data': '{{ route('admin.parties.data') }}',
                'admin.parties.show': '{{ route('admin.parties.show', ':id') }}',
                'admin.parties.edit': '{{ route('admin.parties.edit', ':id') }}',
                'admin.parties.update': '{{ route('admin.parties.update', ':id') }}',
                'admin.parties.destroy': '{{ route('admin.parties.destroy', ':id') }}',
                'admin.parties.approve': '{{ route('admin.parties.approve', ':id') }}',
                'admin.parties.reject': '{{ route('admin.parties.reject', ':id') }}',
                'admin.parties.tab-data': '{{ route('admin.parties.tab-data', ':id') }}',
                
                // User Routes
                'admin.users.index': '{{ route('admin.users.index') }}',
                'admin.users.data': '{{ route('admin.users.data') }}',
                'admin.users.show': '{{ route('admin.users.show', ':id') }}',
                'admin.users.edit': '{{ route('admin.users.edit', ':id') }}',
                'admin.users.update': '{{ route('admin.users.update', ':id') }}',
                'admin.users.destroy': '{{ route('admin.users.destroy', ':id') }}',
                
                // Order Routes
                'admin.orders.index': '{{ route('admin.orders.index') }}',
                'admin.orders.data': '{{ route('admin.orders.data') }}',
                'admin.orders.show': '{{ route('admin.orders.show', ':id') }}',
                
                // Visit Routes
                'admin.visits.index': '{{ route('admin.visits.index') }}',
                'admin.visits.data': '{{ route('admin.visits.data') }}',
                'admin.visits.show': '{{ route('admin.visits.show', ':id') }}',
                
                // Attendance Routes
                'admin.attendance.index': '{{ route('admin.attendance.index') }}',
                'admin.attendance.data': '{{ route('admin.attendance.data') }}',
                
                // Report Routes
                'admin.reports.index': '{{ route('admin.reports.index') }}',
                'admin.reports.expenses': '{{ route('admin.reports.expenses') }}',
                'admin.reports.field-activity': '{{ route('admin.reports.field-activity') }}',
                
                // Settings Routes
                'admin.settings.index': '{{ route('admin.settings.index') }}',
                'admin.settings.update': '{{ route('admin.settings.update', ':key') }}',
                
                // Holiday Routes
                'admin.holidays.index': '{{ route('admin.holidays.index') }}',
                'admin.holidays.store': '{{ route('admin.holidays.store') }}',
                'admin.holidays.destroy': '{{ route('admin.holidays.destroy', ':id') }}',
                
                // API Routes (if accessed from admin panel)
                'api.admin.approvals.counts': '{{ route('api.admin.approvals.counts') }}',
                'api.admin.approvals.team': '{{ route('api.admin.approvals.team') }}',
                'api.admin.approvals.day-starts': '{{ route('api.admin.approvals.day-starts') }}',
                'api.admin.approvals.expenses': '{{ route('api.admin.approvals.expenses') }}',
                
                // Add more routes as needed
            });
            
            console.log('Laravel routes registered for JavaScript');
        } else {
            console.warn('Route helper not loaded yet. Make sure app.js is included before this script.');
        }
    });
</script>
