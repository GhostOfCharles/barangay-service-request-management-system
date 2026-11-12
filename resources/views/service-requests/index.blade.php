@extends('layouts.app')

@section('title', 'Service Requests')
@section('reopenModal', ($errors->createRequest->any() || request()->boolean('new')) ? 'newRequestModal' : '')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Service Requests</h1>
            <p class="page-subtitle mb-0">Search and filter all requests · {{ $totalRequests }} total</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                <i class="bi bi-plus-lg"></i> Add New Service Request
            </button>
        </div>
    </div>

    {{-- Search by reference number or name; filter by service type, status, and date (FR-06). --}}
    {{-- On phones the search box stays on the page and the other filters open in a bottom sheet. --}}
    @php($activeFilterCount = count(array_filter(\Illuminate\Support\Arr::only($filters, ['service_type', 'status', 'date_from', 'date_to']))))
    <form method="GET" action="{{ route('service-requests.index') }}" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3" role="search">
        <div class="input-group filter-search" style="max-width: 280px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control border-start-0"
                   placeholder="Reference no. or resident name…" aria-label="Search requests" enterkeyhint="search">
        </div>

        <button type="button" class="btn btn-outline-secondary bg-white filter-toggle d-md-none" data-bs-toggle="offcanvas" data-bs-target="#requestFilters"
                aria-controls="requestFilters">
            <i class="bi bi-sliders"></i> Filters
            @if ($activeFilterCount)
                <span class="filter-count">{{ $activeFilterCount }}<span class="visually-hidden"> active</span></span>
            @endif
        </button>

        <div class="offcanvas-md offcanvas-bottom filter-sheet" tabindex="-1" id="requestFilters" aria-labelledby="requestFiltersLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h6 fw-bold" id="requestFiltersLabel">Filter requests</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#requestFilters" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <select name="service_type" class="form-select" style="max-width: 220px;" aria-label="Filter by service type">
                    <option value="">All Service Types</option>
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" @selected((string) ($filters['service_type'] ?? '') === (string) $serviceType->id)>{{ $serviceType->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-select" style="max-width: 190px;" aria-label="Filter by status">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>

                <div class="date-range d-flex align-items-center gap-1">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="Received from" title="Received from">
                    <span class="text-secondary small">to</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="Received to" title="Received to">
                </div>

                <button type="submit" class="btn btn-outline-secondary bg-white d-none d-md-inline-block">Search</button>
                <button type="submit" class="btn btn-success d-md-none">Show results</button>
                @if (array_filter($filters))
                    <a href="{{ route('service-requests.index') }}" class="btn btn-link text-secondary">Clear<span class="d-md-none"> filters</span></a>
                @endif
            </div>
        </div>
    </form>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th class="d-none d-md-table-cell">Reference</th>
                    <th>Resident</th>
                    <th class="d-none d-lg-table-cell">Service Type</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell text-end">Amount</th>
                    <th class="d-none d-md-table-cell">Received</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($serviceRequests as $serviceRequest)
                    <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                        <td class="ref-no d-none d-md-table-cell">{{ $serviceRequest->reference_no }}</td>
                        <td class="fw-medium">
                            {{ $serviceRequest->resident->full_name }}
                            <div class="cell-sub d-lg-none">
                                <span class="d-md-none">{{ $serviceRequest->reference_no }} · </span>{{ $serviceRequest->serviceType->name }}<span class="d-md-none"> · {{ $serviceRequest->received_at->format('M j') }}</span>
                            </div>
                        </td>
                        <td class="d-none d-lg-table-cell">{{ $serviceRequest->serviceType->name }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="d-none d-sm-table-cell text-end"><x-peso :amount="$serviceRequest->fee_amount" /></td>
                        <td class="d-none d-md-table-cell text-nowrap">{{ $serviceRequest->received_at->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small"><span class="d-none d-sm-inline">View<span class="d-none d-xl-inline"> Details</span></span><i class="bi bi-chevron-right row-chevron d-sm-none" aria-hidden="true"></i><span class="visually-hidden d-sm-none">View {{ $serviceRequest->reference_no }}</span></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">No matching records found. Clear a filter or check the spelling.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="status-legend">Statuses: Submitted → Under Review → Approved → Cash Payment → Completed. Services with a ₱0.00 fee skip Cash Payment.</p>

    <div class="mt-3">{{ $serviceRequests->links() }}</div>
@endsection

@push('modals')
    {{-- Screen 11: Add New Service Request (Staff-Assisted) --}}
    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('service-requests.store') }}" enctype="multipart/form-data" novalidate data-submission-form class="d-contents">
                    @csrf
                    <div class="modal-form-header">
                        <h2 class="h5 fw-bold" id="newRequestModalLabel">Add New Service Request <span class="fw-normal text-secondary d-block d-sm-inline fs-6">(Staff-Assisted)</span></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">

                        @php($selectedResident = $residents->firstWhere('id', (int) old('resident_id', request('resident'))))

                        <x-submission-fields :options="$serviceTypeOptions" bag="createRequest">
                            <x-slot:before>
                                {{-- Searchable resident field; residents who are not on file are added on the Residents page first. --}}
                                <div class="mb-3" data-resident-picker data-add-resident-url="{{ route('residents.index', ['new' => 1]) }}">
                                    <label for="resident-search" class="form-label">Resident</label>
                                    <div class="position-relative">
                                        <input type="hidden" name="resident_id" value="{{ $selectedResident?->id }}" data-resident-id>
                                        <input type="text" id="resident-search" data-resident-search
                                               value="{{ $selectedResident ? $selectedResident->full_name.' ('.$selectedResident->purok.')' : '' }}"
                                               class="form-control @error('resident_id', 'createRequest') is-invalid @enderror"
                                               placeholder="Type a name, purok, or contact no…" autocomplete="off"
                                               role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="resident-options">
                                        <ul id="resident-options" class="resident-options list-group d-none" role="listbox" data-resident-options></ul>
                                        @error('resident_id', 'createRequest')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <script type="application/json" data-residents>@json($residentOptions)</script>
                                </div>
                            </x-slot:before>

                            <x-slot:after>
                                <div class="mb-3">
                                    <label for="createRequest-received-at" class="form-label">Date Received</label>
                                    <input type="date" id="createRequest-received-at" name="received_at" max="{{ today()->toDateString() }}"
                                           value="{{ old('received_at', today()->toDateString()) }}"
                                           class="form-control @error('received_at', 'createRequest') is-invalid @enderror" required>
                                    @error('received_at', 'createRequest')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </x-slot:after>
                        </x-submission-fields>

                        <p class="small text-secondary mt-3 mb-0">Use this form when a resident visits the office and cannot submit online. A reference number is generated on save.</p>
                    </div>
                    <div class="modal-form-footer">
                        <span class="small text-secondary me-auto footer-hint" data-submit-hint aria-live="polite"></span>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" data-submit>Save Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
