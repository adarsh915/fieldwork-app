@extends('admin.layouts.app')

@section('title', 'Expense Bill')
@section('breadcrumb', 'Admin / Expense Bill')
@section('page_title', 'My Expense Bill')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'expenses',
        'title' => 'Expense Bills',
        'subtitle' => 'Expense entries (mock). Frontend-only CRUD.',
        'columns' => 'billNo:Bill No|date:Date|category:Category|amount:Amount|status:Status',
        'seed' => json_encode([
            ['billNo' => 'EXP-001', 'date' => '2026-05-25', 'category' => 'Fuel', 'amount' => '800', 'status' => 'Pending'],
            ['billNo' => 'EXP-002', 'date' => '2026-05-24', 'category' => 'Food', 'amount' => '220', 'status' => 'Approved'],
        ]),
    ])
@endsection
