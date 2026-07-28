@extends('admin.layouts.app')

@section('title', 'Product Demo')
@section('breadcrumb', 'Admin / Product Demo')
@section('page_title', 'Product Demo')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'product_demo',
        'title' => 'Product Demo Requests',
        'subtitle' => 'Demo requests (mock, frontend-only).',
        'columns' => 'party:Party|product:Product|date:Date|location:Location|status:Status',
        'seed' => json_encode([
            ['party' => '20 MICRONS', 'product' => 'AMPL-X', 'date' => '2026-05-27', 'location' => 'VADODDARA', 'status' => 'Open'],
        ]),
    ])
@endsection
