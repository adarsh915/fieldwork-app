@extends('admin.layouts.app')

@section('title', 'Leave')
@section('breadcrumb', 'Admin / Leave')
@section('page_title', 'Leave')

@section('content')
    @include('admin.partials.crud-page', [
        'module' => 'leave',
        'title' => 'Leave Requests',
        'subtitle' => 'Apply leave + status tracking (mock, frontend-only).',
        'columns' => 'from:From|to:To|reason:Reason|status:Status',
        'seed' => json_encode([
            ['from' => '2026-05-28', 'to' => '2026-05-29', 'reason' => 'Personal', 'status' => 'Pending'],
        ]),
    ])
@endsection
