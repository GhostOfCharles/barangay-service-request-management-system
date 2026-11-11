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

    <div class="row g-3 mb-3">
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

    <div class="panel mb-3">
        <div class="panel-header">My Service Requests</div>
        <div class="table-responsive">
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

    @if ($tracked)
        @php($latestLog = $tracked->logs->sortByDesc('id')->first())
        <div class="panel p-3" id="tracker">
            <h2 class="h6 fw-bold mb-3">Status Tracker — {{ $tracked->reference_no }} ({{ $tracked->serviceType->name }})</h2>
            <x-status-tracker :service-request="$tracked" />

            @if ($tracked->status === \App\Enums\RequestStatus::CashPayment)
                <div class="tracker-note">
                    Ready for fulfillment. Present reference number <strong>{{ $tracked->reference_no }}</strong> at the barangay hall
                    and pay <strong><x-peso :amount="$tracked->amountDue()" /></strong> in cash upon receiving your document.
                </div>
            @else
                <div class="tracker-note is-plain">
                    {{ $tracked->status->residentInstruction() }}
                    @if ($latestLog?->entry_type === \App\Enums\LogEntryType::Note)
                        <br><span class="small">Latest note from staff: {{ $latestLog->note }}</span>
                    @elseif ($latestLog)
                        <br><span class="small text-secondary">Last updated {{ $latestLog->created_at->format('M j, Y g:i A') }}.</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
@endsection
