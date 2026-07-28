@extends('admin.layouts.app')

@section('title', 'Activities')
@section('breadcrumb', 'Admin / Activities')
@section('page_title', 'Activities')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'activities',
        'title' => 'Activities',
        'subtitle' => 'Mobile app ke Activities module ka admin version (mock).',
        'columns' => 'date:Date|type:Type|remarks:Remarks|owner:Owner',
        'seed' => json_encode([
            ['date' => '2026-05-25', 'type' => 'Party Visit', 'remarks' => 'Order discussion', 'owner' => 'Ritik Patel'],
            ['date' => '2026-05-24', 'type' => 'Office Visit', 'remarks' => 'Stock review', 'owner' => 'Ritik Patel'],
        ]),
    ])
@endsection
