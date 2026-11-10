@extends('layouts.app')

@section('title', 'Service Requests')
@section('reopenModal', ($errors->createRequest->any() || request()->boolean('new')) ? 'newRequestModal' : '')

@php
    $activeStatus = $filters['status'] ?? null;
    $baseFilters = array_filter(\Illuminate\Support\Arr::except($filters, 'status'));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Service Requests</h1>
            <p class="page-subtitle mb-0">{{ $totalRequests }} total {{ Str::plural('request', $totalRequests) }}</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> New Service Request
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('service-requests.index') }}" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3" role="search">
        @if ($activeStatus)
            <input type="hidden" name="status" value="{{ $activeStatus }}">
        @endif

        <div class="input-group search">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control border-start-0"
                   placeholder="Search resident or ref. no…" aria-label="Search requests">
        </div>

        <div class="pill-group" role="group" aria-label="Filter by status">
            <a href="{{ route('service-requests.index', $baseFilters) }}" @class(['active' => ! $activeStatus])>All</a>
            @foreach ($statuses as $status)
                <a href="{{ route('service-requests.index', [...$baseFilters, 'status' => $status->value]) }}"
                   @class(['active' => $activeStatus === $status->value])>{{ $status->label() }}</a>
            @endforeach
        </div>

        <select name="category" class="form-select" aria-label="Filter by category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->label() }}</option>
            @endforeach
        </select>

        <div class="date-range d-flex align-items-center gap-1">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="Date received from" title="Date received from">
            <span class="text-secondary small">to</span>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="Date received to" title="Date received to">
        </div>

        <button type="submit" class="btn btn-outline-secondary">Search</button>
        @if (array_filter($filters))
            <a href="{{ route('service-requests.index') }}" class="btn btn-link text-secondary">Clear</a>
        @endif
    </form>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th class="d-none d-md-table-cell">Reference No.</th>
                    <th>Resident</th>
                    <th class="d-none d-md-table-cell">Category</th>
                    <th class="d-none d-xl-table-cell">Description</th>
                    <th>Status</th>
                    <th class="d-none d-sm-table-cell">Date Received</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($serviceRequests as $serviceRequest)
                    <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                        <td class="ref-no d-none d-md-table-cell">{{ $serviceRequest->reference_no }}</td>
                        <td class="row-name">
                            {{ $serviceRequest->resident->name }}
                            <div class="cell-sub d-md-none"><span class="ref-no">{{ $serviceRequest->reference_no }}</span><span>{{ $serviceRequest->category->shortLabel() }}</span><span class="d-sm-none">{{ $serviceRequest->date_received->format('M j') }}</span></div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                        <td class="text-secondary d-none d-xl-table-cell">{{ Str::limit($serviceRequest->description, 40, '…') }}</td>
                        <td><x-status-badge :status="$serviceRequest->status" /></td>
                        <td class="text-nowrap d-none d-sm-table-cell">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small">View<span class="d-none d-xl-inline"> Details</span></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">No matching records found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $serviceRequests->links() }}</div>
@endsection

@push('modals')
    <div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" action="{{ route('service-requests.store') }}" novalidate>
                    @csrf
                    <div class="modal-body p-4">
                        <h2 class="mb-3" id="newRequestModalLabel">New Service Request</h2>

                        @php
                            $isNewResident = old('new_resident') === '1';
                            $selectedResident = $residents->firstWhere('id', (int) old('resident_id', request('resident')));
                        @endphp

                        {{-- Searchable resident field; "New resident" adds one without leaving this form. --}}
                        <div class="mb-3" data-resident-picker>
                            <input type="hidden" name="new_resident" value="{{ $isNewResident ? 1 : 0 }}" data-new-flag>

                            <div data-existing-section @class(['d-none' => $isNewResident])>
                                <label for="resident-search" class="form-label">Resident</label>
                                <div class="position-relative">
                                    <input type="hidden" name="resident_id" value="{{ $selectedResident?->id }}" data-resident-id>
                                    <input type="text" id="resident-search" data-resident-search
                                           value="{{ $selectedResident ? $selectedResident->name.' ('.$selectedResident->purok.')' : '' }}"
                                           class="form-control @error('resident_id', 'createRequest') is-invalid @enderror"
                                           placeholder="Type a name, purok, or contact no…" autocomplete="off"
                                           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="resident-options">
                                    <ul id="resident-options" class="resident-options list-group d-none" role="listbox" data-resident-options></ul>
                                    @error('resident_id', 'createRequest')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="button" class="btn btn-link btn-sm px-0 mt-1" data-show-new>
                                    <i class="bi bi-person-plus" aria-hidden="true"></i> Add a new resident
                                </button>
                            </div>

                            <div data-new-section @class(['d-none' => ! $isNewResident])>
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold">New resident</span>
                                    <button type="button" class="btn btn-link btn-sm px-0" data-show-existing>Pick from the list</button>
                                </div>
                                <div class="row g-2">
                                    @foreach ([
                                        ['name', 'Name', 'col-12', 'Enter name'],
                                        ['address', 'Address', 'col-12', 'Enter address'],
                                        ['contact_number', 'Contact (optional)', 'col-sm-6', 'Enter contact'],
                                        ['purok', 'Purok', 'col-sm-6', 'Enter purok'],
                                    ] as [$field, $label, $columnClass, $placeholder])
                                        <div class="{{ $columnClass }}">
                                            <label for="new-resident-{{ $field }}" class="form-label">{{ $label }}</label>
                                            <input type="text" id="new-resident-{{ $field }}" name="resident[{{ $field }}]"
                                                   value="{{ old('resident.'.$field) }}" placeholder="{{ $placeholder }}"
                                                   @if ($field === 'name') data-new-resident-name @endif
                                                   class="form-control @error('resident.'.$field, 'createRequest') is-invalid @enderror">
                                            @error('resident.'.$field, 'createRequest')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <script type="application/json" data-residents>@json($residentOptions)</script>
                        </div>

                        @include('service-requests._form-fields', ['bag' => 'createRequest', 'serviceRequest' => null])

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Save Request</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
