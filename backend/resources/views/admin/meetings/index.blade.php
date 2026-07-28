@extends('admin.layouts.app')

@section('title', 'My Meeting')
@section('breadcrumb', 'Admin / My Meeting')
@section('page_title', 'My Meeting')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'meetings',
        'title' => 'Meetings',
        'subtitle' => 'Meeting scheduler (mock). Frontend-only CRUD.',
        'columns' => 'date:Date|time:Time|with:With|agenda:Agenda|status:Status',
        'seed' => json_encode([
            ['date' => '2026-05-26', 'time' => '11:00', 'with' => 'Dealer', 'agenda' => 'Order follow-up', 'status' => 'Planned'],
        ]),
    ])
@endsection
