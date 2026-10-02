@extends('admin.layouts.app')

@section('title', 'Expense Statement')
@section('breadcrumb', 'Admin / Expenses / Statement')
@section('page_title', 'Expense Statement')

@section('content')
<div class="mb-4 no-print flex justify-between items-center">
    <a href="{{ route('admin.expenses.reports.monthly') }}" class="btn-secondary text-sm px-3 py-1.5 inline-flex items-center gap-2">
        <x-lucide-arrow-left class="w-4 h-4" /> Back to Monthly Summary
    </a>
    <button onclick="window.print()" class="btn-primary text-sm px-3 py-1.5 inline-flex items-center gap-2 shadow">
        <x-lucide-printer class="w-4 h-4" /> Print Statement
    </button>
</div>

{{-- Printable Area --}}
<div class="panel p-8 bg-white" id="printable-statement">
    
    {{-- Header --}}
    <div class="flex justify-between items-start mb-8 pb-6 border-b-2 border-slate-200">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Expense Statement</h1>
            <p class="text-slate-500 mt-1 font-medium">Reimbursement Report</p>
        </div>
        <div class="text-right">
            <h2 class="text-xl font-bold text-slate-800">{{ $user->name }}</h2>
            <p class="text-slate-500">Employee ID: #{{ $user->id }}</p>
            <p class="text-brand-600 font-bold mt-2 bg-brand-50 px-3 py-1 rounded-md inline-block">Period: {{ $month->format('F Y') }}</p>
        </div>
    </div>

    {{-- Details Table --}}
    <div class="mb-8">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 text-slate-700 text-xs uppercase tracking-wider">
                    <th class="p-3 border-b border-slate-300 font-bold">Date</th>
                    <th class="p-3 border-b border-slate-300 font-bold text-right">Food</th>
                    <th class="p-3 border-b border-slate-300 font-bold text-right">Hotel</th>
                    <th class="p-3 border-b border-slate-300 font-bold text-right">Travel</th>
                    <th class="p-3 border-b border-slate-300 font-bold text-right">Misc / Other</th>
                    <th class="p-3 border-b border-slate-300 font-bold text-right text-brand-700 bg-brand-50">Auto Travel</th>
                    <th class="p-3 border-b border-slate-300 font-black text-right text-slate-900">Day Total</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($statementData as $data)
                    <tr class="hover:bg-slate-50 transition border-b border-slate-100">
                        <td class="p-3 font-semibold text-slate-800">{{ $data['date_formatted'] }}</td>
                        <td class="p-3 text-right text-slate-600">{{ $data['food'] > 0 ? '₹'.number_format($data['food'], 2) : '-' }}</td>
                        <td class="p-3 text-right text-slate-600">{{ $data['hotel'] > 0 ? '₹'.number_format($data['hotel'], 2) : '-' }}</td>
                        <td class="p-3 text-right text-slate-600">{{ $data['travel'] > 0 ? '₹'.number_format($data['travel'], 2) : '-' }}</td>
                        <td class="p-3 text-right text-slate-600">
                            {{ ($data['misc'] + $data['other']) > 0 ? '₹'.number_format($data['misc'] + $data['other'], 2) : '-' }}
                        </td>
                        <td class="p-3 text-right font-medium text-brand-600 bg-brand-50/50">{{ $data['system_travel'] > 0 ? '₹'.number_format($data['system_travel'], 2) : '-' }}</td>
                        <td class="p-3 text-right font-bold text-slate-900">₹{{ number_format($data['day_total'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-500 italic">No approved expenses recorded for this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Summary --}}
    @if($statementData->count() > 0)
    <div class="flex justify-end mt-8">
        <div class="w-full max-w-sm bg-slate-50 rounded-xl p-6 border border-slate-200">
            <h3 class="font-bold text-slate-800 uppercase tracking-widest text-xs mb-4 border-b border-slate-200 pb-2">Category Summary</h3>
            
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">Total Food</span>
                    <span class="font-medium text-slate-900">₹{{ number_format($totals['food'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">Total Hotel</span>
                    <span class="font-medium text-slate-900">₹{{ number_format($totals['hotel'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">Total Manual Travel</span>
                    <span class="font-medium text-slate-900">₹{{ number_format($totals['travel'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-600">Total Misc/Other</span>
                    <span class="font-medium text-slate-900">₹{{ number_format($totals['misc'] + $totals['other'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                    <span class="text-brand-600 font-bold">Total Auto Travel</span>
                    <span class="font-bold text-brand-600">₹{{ number_format($totals['system_travel'], 2) }}</span>
                </div>
                
                <div class="flex justify-between items-center pt-4 mt-2 border-t-2 border-slate-300">
                    <span class="text-lg font-black text-slate-900">Grand Total</span>
                    <span class="text-2xl font-black text-slate-900">₹{{ number_format($totals['grand_total'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="mt-16 text-center text-sm text-slate-400 no-print">
        <p>This statement only includes Approved expenses and System-generated travel calculations.</p>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-statement, #printable-statement * {
            visibility: visible;
        }
        #printable-statement {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
            border: none;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
@endsection