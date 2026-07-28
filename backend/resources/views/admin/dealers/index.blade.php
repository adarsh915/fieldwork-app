@extends('admin.layouts.app')

@section('title', 'Dealer List')
@section('breadcrumb', 'Admin / Dealer List')
@section('page_title', 'Dealer List')

@section('content')
    <section class="panel" data-ui-dealer-menu>
        <div class="panel-header">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Dealers</h2>
                    <p class="mt-1 text-sm text-slate-500">Mobile style cards + 3-dot menu (frontend-only).</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:flex-row xl:max-w-xl">
                    <input class="form-input" placeholder="Search (UI only)" />
                    <div class="flex gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Back</a>
                        <button type="button" class="btn-primary">Add Dealer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4">
                @foreach (range(1, 4) as $i)
                    <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
                        <div class="flex">
                            <div class="w-2 bg-rose-500"></div>
                            <div class="flex-1 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="font-black text-slate-950 text-sm">
                                        292 - 20 MICRONS LIMITED - BARODA - VADODDARA
                                    </div>
                                    <button type="button" class="btn-secondary px-2.5 py-1.5 text-xs" data-ui-dealer-open>⋮</button>
                                </div>
                                <div class="mt-2 text-sm font-bold text-slate-900">📞 9925002964</div>
                                <div class="mt-1 text-xs font-bold text-slate-900">Officer : MAYURIKUMAR BHIKHUBHAI SHILU</div>
                                <div class="mt-1 text-xs font-bold text-slate-900">PLOT NO.347 G.I.D.C.. WAGHODIA DIST . VADODARA.. WAGHODIA</div>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="text-sm font-black">
                                        <span class="text-rose-600">Bal :</span>
                                        <span class="text-emerald-600">0Db</span>
                                    </div>
                                    <div class="text-emerald-600 text-sm font-black">WhatsApp</div>
                                    <div class="ml-auto text-sm font-black text-slate-900">CL:0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="fixed inset-0 z-50 hidden bg-black/10" data-ui-dealer-overlay>
        <div class="flex h-full w-full items-center justify-center p-4">
            <div class="w-full max-w-sm rounded-xl bg-white shadow-xl ring-1 ring-slate-200" data-ui-dealer-card>
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div class="font-black text-slate-950">Actions</div>
                    <button class="btn-secondary px-2.5 py-1.5 text-xs" data-ui-dealer-close>Close</button>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach (['Ledger','Visit','Order','Payment','Find Location','Party History','Contact Dealer','Add Reminder','History','Bill'] as $a)
                        <button type="button" class="w-full px-4 py-3 text-left font-bold text-slate-900 hover:bg-slate-50">{{ $a }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
