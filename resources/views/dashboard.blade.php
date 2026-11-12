@extends('layouts.app')

@section('title', 'Dashboard')

@php
    use App\Enums\RequestStatus;

    $statusCards = [
        [RequestStatus::Submitted, 'stat-submitted'],
        [RequestStatus::UnderReview, 'stat-pending'],
        [RequestStatus::Approved, 'stat-progress'],
        [RequestStatus::CashPayment, 'stat-cash'],
    ];
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle mb-0">Today is {{ today()->format('M j, Y') }} · Welcome back, {{ auth()->user()->name }}</p>
        </div>
        <div class="page-actions is-pair d-flex gap-2">
            <a href="{{ route('service-requests.index', ['new' => 1]) }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> New <span class="d-none d-sm-inline">Service </span>Request
            </a>
            <a href="{{ route('residents.index') }}" class="btn btn-outline-secondary bg-white">
                <i class="bi bi-search"></i> <span class="d-none d-sm-inline">Search </span>Resident<span class="d-sm-none">s</span>
            </a>
        </div>
    </div>

    {{-- Requests by status: each card opens Service Requests with that filter applied. --}}
    <div class="row g-3 mb-3 row-cols-2 row-cols-md-3 row-cols-xl-5 stat-grid">
        @foreach ($statusCards as [$status, $class])
            <div class="col">
                <a href="{{ route('service-requests.index', ['status' => $status->value]) }}" class="stat-link">
                    <div class="stat-card {{ $class }}">
                        <div class="stat-label">{{ $status->label() }}</div>
                        <div class="stat-value">{{ $statusCounts[$status->value] ?? 0 }}</div>
                    </div>
                </a>
            </div>
        @endforeach
        <div class="col">
            <a href="{{ route('service-requests.index', ['status' => RequestStatus::Completed->value]) }}" class="stat-link">
                <div class="stat-card stat-completed">
                    <div class="stat-label">Completed Today</div>
                    <div class="stat-value">{{ $completedTodayCount }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3 stat-grid">
        <div class="col-4">
            <a href="{{ route('residents.index', ['verification' => 'pending']) }}" class="stat-link">
                <div class="stat-card stat-pending">
                    <div class="stat-label">Pending Verifications</div>
                    <div class="stat-value">{{ $pendingVerifications }}</div>
                </div>
            </a>
        </div>
        <div class="col-4">
            <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="stat-link">
                <div class="stat-card stat-progress">
                    <div class="stat-label">Open Tickets</div>
                    <div class="stat-value">{{ $openTickets }}</div>
                </div>
            </a>
        </div>
        <div class="col-4">
            <a href="{{ route('residents.index', ['verification' => 'verified']) }}" class="stat-link">
                <div class="stat-card stat-plain">
                    <div class="stat-label">Total Residents</div>
                    <div class="stat-value">{{ $residentCount }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center">
            <span>Recent Service Requests</span>
            <a href="{{ route('service-requests.index') }}" class="small fw-normal">View all requests</a>
        </div>
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th class="d-none d-md-table-cell">Reference</th>
                    <th>Resident</th>
                    <th class="d-none d-md-table-cell">Service</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell">Received</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentRequests as $serviceRequest)
                    <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                        <td class="ref-no d-none d-md-table-cell">{{ $serviceRequest->reference_no }}</td>
                        <td class="fw-medium">
                            {{ $serviceRequest->resident->full_name }}
                            <div class="cell-sub d-md-none">{{ $serviceRequest->reference_no }} · {{ $serviceRequest->serviceType->name }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $serviceRequest->serviceType->name }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $serviceRequest->received_at->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small"><span class="d-none d-sm-inline">View<span class="d-none d-xl-inline"> Details</span></span><i class="bi bi-chevron-right row-chevron d-sm-none" aria-hidden="true"></i><span class="visually-hidden d-sm-none">View {{ $serviceRequest->reference_no }}</span></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">No service requests recorded yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
