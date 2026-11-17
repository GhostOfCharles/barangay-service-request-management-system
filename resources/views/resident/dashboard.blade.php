@extends('layouts.app')

@section('title', 'My Requests')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">My Requests</h1>
            <p class="page-subtitle mb-0">Welcome back, {{ $resident->full_name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('resident.requests.create') }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> New Request</a>
        </div>
    </div>

    {{-- data-live regions refresh every few seconds (resources/js/live-poll.js). --}}
    @fragment('resident-stats')
    <div class="row g-3 mb-3 stat-grid" data-live="resident-stats">
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-pending">
                <div class="stat-label">Active Requests</div>
                <div class="stat-value">{{ $activeCount }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-cash">
                <div class="stat-label">Amount Due (Cash)</div>
                <div class="stat-value"><x-peso :amount="$amountDue" /></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-completed">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $completedCount }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('resident.tickets.index') }}" class="stat-link">
                <div class="stat-card stat-progress">
                    <div class="stat-label">Open Tickets</div>
                    <div class="stat-value">{{ $openTickets }}</div>
                </div>
            </a>
        </div>
    </div>
    @endfragment

    @fragment('resident-requests')
    <div class="panel mb-3" data-live="resident-requests">
        <div class="panel-header">My Service Requests</div>

        <div class="request-cards d-sm-none">
            @forelse ($serviceRequests as $serviceRequest)
                <button type="button" class="request-card" data-bs-toggle="modal" data-bs-target="#track-{{ $serviceRequest->id }}">
                    <span class="request-card-main">
                        <span class="request-card-title d-block">{{ $serviceRequest->serviceType->name }}</span>
                        <span class="request-card-meta">
                            <x-status-badge :status="$serviceRequest->status" />
                            <span class="ref-no">{{ $serviceRequest->reference_no }}</span>
                            @if ((float) $serviceRequest->amountDue() > 0)
                                <span>Due <x-peso :amount="$serviceRequest->amountDue()" /></span>
                            @endif
                        </span>
                    </span>
                    <i class="bi bi-chevron-right row-chevron" aria-hidden="true"></i>
                </button>
            @empty
                <p class="text-center text-secondary p-4 mb-0">
                    You have no requests yet. <a href="{{ route('resident.requests.create') }}">Start a new request</a>.
                </p>
            @endforelse
        </div>

        <div class="table-responsive d-none d-sm-block">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Reference No.</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th class="text-end">Amount Due</th>
                    <th class="d-none d-sm-table-cell">Submitted</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($serviceRequests as $serviceRequest)
                    <tr @class(['table-active' => $tracked?->is($serviceRequest)])>
                        <td class="ref-no fw-semibold">{{ $serviceRequest->reference_no }}</td>
                        <td>{{ $serviceRequest->serviceType->name }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="text-end"><x-peso :amount="$serviceRequest->amountDue()" /></td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $serviceRequest->received_at->format('M j, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('resident.dashboard', ['track' => $serviceRequest->id]) }}#tracker" class="small fw-semibold">Track</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            You have no requests yet. <a href="{{ route('resident.requests.create') }}">Start a new request</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endfragment

    @fragment('resident-tracker')
    <div data-live="resident-tracker">
        @if ($tracked)
            <div class="panel p-3 d-none d-sm-block" id="tracker">
                <h2 class="h6 fw-bold mb-3">Status Tracker — {{ $tracked->reference_no }} ({{ $tracked->serviceType->name }})</h2>
                @include('resident._tracker', ['serviceRequest' => $tracked])
            </div>
        @endif
    </div>
    @endfragment
@endsection

@push('modals')
    {{-- Phones: each request's tracker opens as a bottom sheet. After submitting, the new request's sheet opens.
         While a sheet is open, only the tracker inside it refreshes. --}}
    @fragment('resident-sheets')
    <div data-live="resident-sheets">
    @foreach ($serviceRequests as $serviceRequest)
        <div class="modal fade modal-sheet" id="track-{{ $serviceRequest->id }}" tabindex="-1" aria-labelledby="track-{{ $serviceRequest->id }}-label" aria-hidden="true"
             @if (request()->filled('track') && $tracked?->is($serviceRequest)) data-show-on-phone @endif>
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-body p-4">
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="flex-grow-1">
                                <h2 class="h5 fw-bold mb-0" id="track-{{ $serviceRequest->id }}-label">{{ $serviceRequest->serviceType->name }}</h2>
                                <div class="ref-no">{{ $serviceRequest->reference_no }} · Submitted {{ $serviceRequest->received_at->format('M j, Y') }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        @fragment('track-body-'.$serviceRequest->id)
                        <div data-live="track-body-{{ $serviceRequest->id }}">
                            @include('resident._tracker', ['serviceRequest' => $serviceRequest])
                        </div>
                        @endfragment
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>
    @endfragment
@endpush
