@extends('admin.layouts.app')

@section('title', 'Day Start')
@section('breadcrumb', 'Admin / Day Start')
@section('page_title', 'Day Start')

@section('content')
    <section class="panel" data-ui-daystart>
        <div class="panel-header">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Day Start</h2>
                    <p class="mt-1 text-sm text-slate-500">Mobile screen ka web admin version (frontend-only, mock actions).</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Back</a>
                    <button class="btn-primary">Day Start Now</button>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Tour Purpose</label>
                            <select class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]">
                                <option>Party Visit (Order/Payment)</option>
                                <option>Field Visit (Field Day)</option>
                                <option>Office Visit</option>
                                <option>Work From Home</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Tour Type</label>
                            <select class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]">
                                <option>In Headquarter</option>
                                <option>Out Of Headquarter</option>
                                <option>Same Day Return</option>
                                <option>Tour With Senior</option>
                                <option>Work From Home</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Vehicle Type</label>
                            <select class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]">
                                <option>Two Wheeler Personal</option>
                                <option>Four Wheeler Personal</option>
                                <option>Two Wheel Company</option>
                                <option>Four Wheel Company</option>
                                <option>By Other</option>
                                <option>Multi Vehical</option>
                                <option>Work From Home</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Vehicle No.</label>
                            <input class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]" placeholder="GJ-xx-xxxx" />
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Place To Visit</label>
                            <input class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]" placeholder="Multiple places, comma separated" />
                        </div>

                        <button type="button" class="btn-primary w-full py-3 text-base" data-ui-party-open>
                            Select Parties To Visit
                        </button>
                        <div class="text-sm font-bold text-slate-500">Selected: <span data-ui-party-selected>0 selected</span></div>

                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-[var(--ampl-primary)]">Opening K.M.</label>
                            <input class="form-input mt-2 border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]" placeholder="0" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex h-full flex-col items-center justify-center gap-4">
                        <div class="text-6xl text-[var(--ampl-primary)]">📷</div>
                        <div class="text-sm font-bold text-slate-500">Camera placeholder (frontend-only)</div>
                        <div class="grid w-full grid-cols-2 gap-3">
                            <button class="btn-secondary py-3">Cancel</button>
                            <button class="btn-primary py-3">Day Start Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="fixed inset-0 z-50 hidden bg-black/10" data-ui-party-overlay>
        <div class="flex h-full w-full items-center justify-center p-4">
            <div class="w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200">
                <div class="flex items-center justify-between bg-[var(--ampl-primary)] px-4 py-3 text-white">
                    <div class="font-black">Select Parties</div>
                    <button type="button" class="btn-secondary px-2.5 py-1.5 text-xs" data-ui-party-close>Close</button>
                </div>
                <div class="p-4">
                    <input class="form-input border-[var(--ampl-primary)] font-black text-[var(--ampl-primary)]" placeholder="Search Dealer" data-ui-party-search />
                </div>
                <div class="max-h-[60vh] overflow-y-auto" data-ui-party-list></div>
                <div class="p-4">
                    <button type="button" class="btn-primary w-full py-3" data-ui-party-close>Done</button>
                </div>
            </div>
        </div>
    </div>
@endsection
