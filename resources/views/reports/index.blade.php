@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle mb-0">Filtered summary of service request activity</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
        </div>
    </div>

    <section class="panel panel-filters mb-4" aria-labelledby="filters-heading">
        <h2 id="filters-heading">Filter criteria</h2>
        <form method="GET" action="{{ route('reports') }}" class="row g-2 align-items-end" novalidate>
            <div class="col-6 col-md-auto">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control @error('date_from') is-invalid @enderror">
            </div>
            <div class="col-6 col-md-auto">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control @error('date_to') is-invalid @enderror" @error('date_to') aria-invalid="true" aria-describedby="report-error" @enderror>
            </div>
            <div class="col-6 col-md-3">
                <label for="category" class="form-label">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-success">Apply Filters</button>
            </div>
            @if ($errors->any())
                <p class="col-12 text-danger small mb-0" id="report-error">{{ $errors->first() }}</p>
            @endif
        </form>
    </section>

    <dl class="stats stats--sheet">
        <div>
            <dt>Total Requests</dt>
            <dd><span class="figure">{{ $totalCount }}</span></dd>
        </div>
        <div>
            <dt>Pending</dt>
            <dd><span class="figure">{{ $statusCounts[\App\Enums\RequestStatus::Pending->value] ?? 0 }}</span></dd>
        </div>
        <div>
            <dt>In Progress</dt>
            <dd><span class="figure">{{ $statusCounts[\App\Enums\RequestStatus::InProgress->value] ?? 0 }}</span></dd>
        </div>
        <div>
            <dt>Completed</dt>
            <dd><span class="figure">{{ $statusCounts[\App\Enums\RequestStatus::Completed->value] ?? 0 }}</span></dd>
        </div>
    </dl>

    <div class="row g-4">
        <div class="col-xl-5">
            <section class="panel" aria-labelledby="category-heading">
                <h2 class="panel-header m-0" id="category-heading">Requests by Category</h2>
                <div class="panel-body">
                    @foreach ($categories as $category)
                        @php($count = $categoryCounts[$category->value] ?? 0)
                        <div class="category-row">
                            <div class="d-flex justify-content-between gap-3">
                                <span>{{ $category->label() }}</span>
                                <span class="figure">{{ $count }}</span>
                            </div>
                            <div class="category-bar" role="img" aria-label="{{ $category->label() }}: {{ $count }}">
                                <span style="width: {{ $totalCount ? round($count / $totalCount * 100) : 0 }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-xl-7">
            <section class="panel" aria-labelledby="results-heading">
                <h2 class="panel-header m-0" id="results-heading">Filtered Results ({{ $results->count() }})</h2>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead>
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
                                <td class="row-name">
                                    {{ $serviceRequest->resident->name }}
                                    <div class="cell-sub d-md-none">
                                        <a class="ref-no" href="{{ route('service-requests.show', $serviceRequest) }}">{{ $serviceRequest->reference_no }}</a>
                                        <span>{{ $serviceRequest->category->shortLabel() }}</span>
                                        <span class="d-sm-none">{{ $serviceRequest->date_received->format('M j') }}</span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                                <td><x-status-badge :status="$serviceRequest->status" /></td>
                                <td class="text-nowrap d-none d-sm-table-cell">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="5">No matching records found. Widen the dates or clear a filter.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
