@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle mb-0">Today is {{ today()->format('M j, Y') }} · Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <div class="page-actions d-flex gap-2">
            <a href="{{ route('service-requests.index', ['new' => 1]) }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> New Service Request
            </a>
            <a href="{{ route('residents.index') }}" class="btn btn-outline-secondary bg-white">
                <i class="bi bi-search"></i> Search Resident
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <a href="{{ route('service-requests.index', ['status' => 'pending']) }}" class="text-decoration-none text-reset">
                <div class="stat-card stat-pending">
                    <div class="stat-label"><span class="dot" style="background:#f5b301"></span>Pending Requests</div>
                    <div class="stat-value">{{ $pendingCount }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('service-requests.index', ['status' => 'in_progress']) }}" class="text-decoration-none text-reset">
                <div class="stat-card stat-progress">
                    <div class="stat-label"><span class="dot" style="background:#0ea5e9"></span>In Progress</div>
                    <div class="stat-value">{{ $inProgressCount }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-completed">
                <div class="stat-label"><span class="dot" style="background:#2e7d32"></span>Completed Today</div>
                <div class="stat-value">{{ $completedTodayCount }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('residents.index') }}" class="text-decoration-none text-reset">
                <div class="stat-card stat-residents">
                    <div class="stat-label"><span class="dot" style="background:#c084fc"></span>Total Residents</div>
                    <div class="stat-value">{{ $residentCount }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center">
            <span>Recent Service Requests</span>
            <a href="{{ route('service-requests.index') }}" class="small fw-normal">View all &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Resident</th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell">Date Received</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentRequests as $serviceRequest)
                    <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                        <td class="fw-medium">
                            {{ $serviceRequest->resident->name }}
                            <div class="cell-sub d-md-none">{{ $serviceRequest->category->shortLabel() }} · {{ $serviceRequest->date_received->format('M j') }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="d-none d-sm-table-cell">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small">View<span class="d-none d-sm-inline"> Details</span></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">No service requests recorded yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
