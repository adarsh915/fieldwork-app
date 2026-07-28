@extends('admin.layouts.app')

@section('title', 'Payments')
@section('breadcrumb', 'Admin / Payments')
@section('page_title', 'Payments (Reg.)')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'payments',
        'title' => 'Payments',
        'subtitle' => 'Payment register (mock). Frontend-only CRUD with localStorage.',
        'columns' => 'receiptNo:Receipt No|party:Party|date:Date|amount:Amount|mode:Mode|status:Status',
        'seed' => json_encode([
            ['receiptNo' => 'PAY-1001', 'party' => '20 MICRONS', 'date' => '2026-05-25', 'amount' => '5000', 'mode' => 'Cash', 'status' => 'Pending'],
            ['receiptNo' => 'PAY-1002', 'party' => 'AMPL INDUSTRIES', 'date' => '2026-05-24', 'amount' => '2000', 'mode' => 'UPI', 'status' => 'Approved'],
        ]),
    ])
@endsection
