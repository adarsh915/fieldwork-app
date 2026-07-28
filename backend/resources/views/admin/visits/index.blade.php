@extends('admin.layouts.app')

@section('title', 'Visits')
@section('breadcrumb', 'Admin / Visits')
@section('page_title', 'Visits')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Party (Customer)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Field Worker</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Check In</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Check Out</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($visits as $visit)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                @if($visit->party)
                                    {{ $visit->party->name }} <br><span class="text-xs text-gray-400">({{ $visit->party->code }})</span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $visit->user ? $visit->user->name : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $visit->check_in_time ? $visit->check_in_time->format('Y-m-d H:i') : 'Pending' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $visit->check_out_time ? $visit->check_out_time->format('Y-m-d H:i') : '--' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium 
                                    @if($visit->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($visit->status == 'completed') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif
                                ">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $visit->notes }}">
                                {{ $visit->notes ?: '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-map-marker-alt text-4xl mb-3 text-gray-300"></i>
                                <p class="text-base font-medium text-gray-600">No visits found</p>
                                <p class="text-sm text-gray-400 mt-1">Field worker visits will appear here once they check in.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $visits->links() }}
        </div>
    </div>
</div>
@endsection
