@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle mb-0">Today is {{ today()->format('M j, Y') }}. Welcome back, {{ auth()->user()->name }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('service-requests.index', ['new' => 1]) }}" class="btn btn-success">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> New Service Request
            </a>
            <a href="{{ route('residents.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-search" aria-hidden="true"></i> Search Resident
            </a>
        </div>
    </div>

    {{-- The day's figures, set as a ruled strip: each links to the list it counts. --}}
    <dl class="stats">
        <div>
            <dt>Pending Requests</dt>
            <dd><a class="figure" href="{{ route('service-requests.index', ['status' => 'pending']) }}">{{ $pendingCount }}</a></dd>
        </div>
        <div>
            <dt>In Progress</dt>
            <dd><a class="figure" href="{{ route('service-requests.index', ['status' => 'in_progress']) }}">{{ $inProgressCount }}</a></dd>
        </div>
        <div>
            <dt>Completed Today</dt>
            <dd><span class="figure">{{ $completedTodayCount }}</span></dd>
        </div>
        <div>
            <dt>Total Residents</dt>
            <dd><a class="figure" href="{{ route('residents.index') }}">{{ $residentCount }}</a></dd>
        </div>
    </dl>

    <section class="panel" aria-labelledby="recent-heading">
        <div class="panel-header d-flex justify-content-between align-items-baseline gap-3">
            <h2 class="m-0" id="recent-heading">Recent Service Requests</h2>
            <a href="{{ route('service-requests.index') }}">View all requests</a>
        </div>
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Resident</th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell">Date Received</th>
                    <th><span class="visually-hidden">Open</span></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentRequests as $serviceRequest)
                    <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                        <td class="row-name">
                            {{ $serviceRequest->resident->name }}
                            <div class="cell-sub d-md-none"><span>{{ $serviceRequest->category->shortLabel() }}</span><span>{{ $serviceRequest->date_received->format('M j') }}</span></div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                        <td class="row-action"><a href="{{ route('service-requests.show', $serviceRequest) }}">View<span class="d-none d-xl-inline"> Details</span></a></td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="5">No service requests recorded yet. <a href="{{ route('service-requests.index', ['new' => 1]) }}">Record the first one</a>.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
