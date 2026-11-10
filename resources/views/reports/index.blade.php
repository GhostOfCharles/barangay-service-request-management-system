@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="mb-3">
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle mb-0">Filtered summary of service request activity</p>
    </div>

    <div class="panel p-3 mb-3">
        <div class="small fw-semibold text-secondary text-uppercase mb-2" style="letter-spacing: .04em;">Filter Criteria</div>
        <form method="GET" action="{{ route('reports') }}" class="row g-2 align-items-end" novalidate>
            <div class="col-6 col-md-auto">
                <label for="date_from" class="form-label small mb-1">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control @error('date_from') is-invalid @enderror">
            </div>
            <div class="col-6 col-md-auto">
                <label for="date_to" class="form-label small mb-1">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control @error('date_to') is-invalid @enderror">
            </div>
            <div class="col-6 col-md-3">
                <label for="category" class="form-label small mb-1">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>{{ $category->label() }}</option>
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
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-success">Apply Filters</button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
            @if ($errors->any())
                <div class="col-12 text-danger small">{{ $errors->first() }}</div>
            @endif
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Total Requests</div>
                <div class="stat-value">{{ $totalCount }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-pending">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $statusCounts[\App\Enums\RequestStatus::Pending->value] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-progress">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ $statusCounts[\App\Enums\RequestStatus::InProgress->value] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card stat-completed">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $statusCounts[\App\Enums\RequestStatus::Completed->value] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Requests by Category</h2>
                @foreach ($categories as $category)
                    @php($count = $categoryCounts[$category->value] ?? 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $category->label() }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                        <div class="category-bar" role="img" aria-label="{{ $category->label() }}: {{ $count }}">
                            <span style="width: {{ $totalCount ? round($count / $totalCount * 100) : 0 }}%; background: {{ $category->color() }};"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel h-100">
                <div class="panel-header">Filtered Results ({{ $results->count() }})</div>
                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-app">
                        <thead class="sticky-top">
                        <tr>
                            <th class="d-none d-md-table-cell">Reference No.</th>
                            <th>Resident</th>
                            <th class="d-none d-md-table-cell">Category</th>
                            <th>Status</th>
                            <th class="d-none d-sm-table-cell">Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($results as $serviceRequest)
                            <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                                <td class="ref-no d-none d-md-table-cell"><a href="{{ route('service-requests.show', $serviceRequest) }}">{{ $serviceRequest->reference_no }}</a></td>
                                <td class="fw-medium">
                                    {{ $serviceRequest->resident->name }}
                                    <div class="cell-sub d-md-none">
                                        <a href="{{ route('service-requests.show', $serviceRequest) }}">{{ $serviceRequest->reference_no }}</a>
                                        · {{ $serviceRequest->category->shortLabel() }}<span class="d-sm-none text-nowrap"> · {{ $serviceRequest->date_received->format('M j') }}</span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                                <td><x-status-badge :status="$serviceRequest->status" /></td>
                                <td class="text-nowrap d-none d-sm-table-cell">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No matching records found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
