@extends('layouts.app')

@section('title', 'Reports')

@php
    use App\Enums\RequestStatus;

    $cards = [
        [RequestStatus::Submitted, 'stat-submitted'],
        [RequestStatus::UnderReview, 'stat-pending'],
        [RequestStatus::Approved, 'stat-progress'],
        [RequestStatus::CashPayment, 'stat-cash'],
        [RequestStatus::Completed, 'stat-completed'],
    ];
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle mb-0">Filtered summary of service requests</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-outline-success bg-white" onclick="window.print()"><i class="bi bi-printer"></i> Print Report</button>
        </div>
    </div>

    <form method="GET" action="{{ route('reports') }}" class="row g-2 align-items-end mb-3" novalidate>
        <div class="col-6 col-md-auto">
            <label for="date_from" class="form-label small mb-1">Date From</label>
            <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control @error('date_from') is-invalid @enderror">
        </div>
        <div class="col-6 col-md-auto">
            <label for="date_to" class="form-label small mb-1">Date To</label>
            <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control @error('date_to') is-invalid @enderror">
        </div>
        <div class="col-6 col-md-3">
            <label for="service_type" class="form-label small mb-1">Service Type</label>
            <select id="service_type" name="service_type" class="form-select">
                <option value="">All Service Types</option>
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" @selected((string) $filters['service_type'] === (string) $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="status" class="form-label small mb-1">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="submit" class="btn btn-success">Apply</button>
        </div>
        @if ($errors->any())
            <div class="col-12 text-danger small">{{ $errors->first() }}</div>
        @endif
    </form>

    <div class="row g-3 mb-3 row-cols-2 row-cols-md-3 row-cols-xl-6">
        <div class="col">
            <div class="stat-card stat-plain">
                <div class="stat-label">Total Requests</div>
                <div class="stat-value">{{ $totalCount }}</div>
            </div>
        </div>
        @foreach ($cards as [$status, $class])
            <div class="col">
                <div class="stat-card {{ $class }}">
                    <div class="stat-label">{{ $status->label() }}</div>
                    <div class="stat-value">{{ $statusCounts[$status->value] ?? 0 }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <div class="panel-header">Requests by Service Type</div>
        @if ($byServiceType->isEmpty())
            <p class="text-center text-secondary py-4 mb-0">No records found. Widen the dates or clear a filter.</p>
        @else
            <div class="table-responsive">
                <table class="table table-app">
                    <thead>
                    <tr>
                        <th>Service Type</th>
                        <th class="text-end">Total</th>
                        <th class="text-end"><span class="d-sm-none">Done</span><span class="d-none d-sm-inline">Completed</span></th>
                        <th class="text-end d-none d-sm-table-cell">Pending</th>
                        <th class="text-end">Cash<span class="d-none d-sm-inline"> Collected</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($byServiceType as $row)
                        <tr>
                            <td class="fw-medium">{{ $row->name }}</td>
                            <td class="text-end">{{ $row->total }}</td>
                            <td class="text-end">{{ $row->completed }}</td>
                            <td class="text-end d-none d-sm-table-cell">{{ $row->total - $row->completed }}</td>
                            <td class="text-end"><x-peso :amount="$row->cash_collected" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="fw-semibold">
                        <td>All service types</td>
                        <td class="text-end">{{ $totalCount }}</td>
                        <td class="text-end">{{ $byServiceType->sum('completed') }}</td>
                        <td class="text-end d-none d-sm-table-cell">{{ $totalCount - $byServiceType->sum('completed') }}</td>
                        <td class="text-end"><x-peso :amount="$cashCollected" /></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <p class="status-legend">Cash collected counts only requests marked Completed by the Administrator. Pending counts every request that is not yet Completed.</p>
@endsection
