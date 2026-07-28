@extends('admin.layouts.app')

@section('title', 'New Deal')
@section('breadcrumb', 'Admin / New Deal')
@section('page_title', 'New Deal')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'new_deal',
        'title' => 'New Deal Pipeline',
        'subtitle' => 'Deal creation + stage tracking (mock, frontend-only).',
        'columns' => 'dealNo:Deal No|party:Party|value:Value|stage:Stage|owner:Owner',
        'seed' => json_encode([
            ['dealNo' => 'ND-001', 'party' => 'AMPL INDUSTRIES', 'value' => '50000', 'stage' => 'Prospect', 'owner' => 'Ritik Patel'],
        ]),
    ])
@endsection
