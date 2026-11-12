@extends('layouts.app')

@section('title', 'Residents')
@section('reopenModal', ($errors->createResident->any() || request()->boolean('new')) ? 'addResidentModal' : '')

@php
    use App\Enums\VerificationStatus;

    $baseQuery = array_filter(['search' => $search]);
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Residents</h1>
            <p class="page-subtitle mb-0">Search, add, and verify resident records · {{ $totalResidents }} on file</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                <i class="bi bi-plus-lg"></i> Add Resident
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('residents.index') }}" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3" role="search">
        @if ($verification)
            <input type="hidden" name="verification" value="{{ $verification->value }}">
        @endif
        <div class="input-group flex-grow-1" style="max-width: 520px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="search" value="{{ $search }}" class="form-control border-start-0"
                   placeholder="Search by name, address, or purok…" aria-label="Search residents">
            <button type="submit" class="btn btn-outline-secondary bg-white">Search</button>
        </div>
        <div class="pill-group" role="group" aria-label="Filter by verification">
            <a href="{{ route('residents.index', $baseQuery) }}" @class(['active' => ! $verification])>All</a>
            <a href="{{ route('residents.index', [...$baseQuery, 'verification' => 'verified']) }}" @class(['active' => $verification === VerificationStatus::Verified])>Verified</a>
            <a href="{{ route('residents.index', [...$baseQuery, 'verification' => 'pending']) }}" @class(['active' => $verification === VerificationStatus::Pending])>Pending<span class="d-none d-sm-inline"> Verification</span></a>
        </div>
    </form>

    <div class="panel mb-3">
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Name</th>
                    <th class="d-none d-md-table-cell">Address</th>
                    <th class="d-none d-sm-table-cell">Purok</th>
                    <th class="d-none d-lg-table-cell">Contact</th>
                    <th>Verification</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($residents as $resident)
                    @php($rowLink = $resident->verification_status === VerificationStatus::Pending
                        ? route('residents.index', [...request()->except('review', 'page'), 'review' => $resident->id]).'#verify'
                        : route('residents.show', $resident))
                    <tr @class(['table-active' => $reviewing?->is($resident)]) data-href="{{ $rowLink }}">
                        <td class="fw-medium">
                            {{ $resident->full_name }}
                            <div class="cell-sub d-sm-none">{{ $resident->purok }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $resident->address }}</td>
                        <td class="d-none d-sm-table-cell">{{ $resident->purok }}</td>
                        <td class="d-none d-lg-table-cell">{{ $resident->contact_number ?? '—' }}</td>
                        <td><x-status-badge :status="$resident->verification_status" kind="verification" /></td>
                        <td class="text-end text-nowrap">
                            @if ($resident->verification_status === VerificationStatus::Pending)
                                <a href="{{ $rowLink }}" class="small fw-semibold">Review ID</a>
                            @else
                                <a href="{{ $rowLink }}" class="small"><span class="d-none d-sm-inline">View</span><i class="bi bi-chevron-right row-chevron d-sm-none" aria-hidden="true"></i><span class="visually-hidden d-sm-none">View {{ $resident->full_name }}</span></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            @if (filled($search))
                                No residents match “{{ $search }}”. You can add them with Add Resident.
                            @elseif ($verification === VerificationStatus::Pending)
                                No registrations are waiting for verification.
                            @else
                                No residents recorded yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-3">{{ $residents->links() }}</div>

    {{-- Verify Residency: compare the uploaded ID with the registration details (FR-09). --}}
    @if ($reviewing)
        <div class="panel p-3" id="verify">
            <h2 class="h6 fw-bold mb-3">Verify Residency — {{ $reviewing->full_name }}</h2>
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="id-preview">
                        @if ($reviewing->valid_id_path)
                            @if (str_ends_with(strtolower($reviewing->valid_id_path), '.pdf'))
                                <iframe src="{{ route('files.valid-id', $reviewing) }}" title="Valid ID of {{ $reviewing->full_name }}"></iframe>
                            @else
                                <img src="{{ route('files.valid-id', $reviewing) }}" alt="Valid ID of {{ $reviewing->full_name }}">
                            @endif
                        @else
                            <span>No valid ID uploaded</span>
                        @endif
                    </div>
                    @if ($reviewing->valid_id_path)
                        <a href="{{ route('files.valid-id', $reviewing) }}" target="_blank" rel="noopener" class="small d-inline-block mt-2">Open the ID in a new tab</a>
                    @endif
                </div>
                <div class="col-md-7">
                    <dl class="row small mb-2">
                        <dt class="col-4 text-secondary fw-normal">Address</dt><dd class="col-8">{{ $reviewing->address }}</dd>
                        <dt class="col-4 text-secondary fw-normal">Purok</dt><dd class="col-8">{{ $reviewing->purok }}</dd>
                        <dt class="col-4 text-secondary fw-normal">Contact</dt><dd class="col-8">{{ $reviewing->contact_number ?? '—' }}</dd>
                        <dt class="col-4 text-secondary fw-normal">Username</dt><dd class="col-8">{{ $reviewing->user?->username ?? '—' }}</dd>
                        <dt class="col-4 text-secondary fw-normal">Registered</dt><dd class="col-8">{{ $reviewing->created_at->format('M j, Y g:i A') }}</dd>
                    </dl>
                    <p class="text-secondary small">Compare the ID with the registration details. The account stays inactive until Staff or the Administrator approve it.</p>

                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <form method="POST" action="{{ route('residents.approve', $reviewing) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">Approve &amp; Activate</button>
                        </form>
                        <form method="POST" action="{{ route('residents.reject', $reviewing) }}" class="d-flex flex-wrap gap-2 flex-grow-1" novalidate>
                            @csrf
                            <label for="rejection-reason" class="visually-hidden">Reason for rejecting</label>
                            <input type="text" id="rejection-reason" name="rejection_reason" value="{{ old('rejection_reason') }}"
                                   class="form-control flex-grow-1 @error('rejection_reason', 'rejectResident') is-invalid @enderror"
                                   style="min-width: 200px; max-width: 320px;" placeholder="Reason, e.g. ID photo is unreadable">
                            <button type="submit" class="btn btn-outline-danger">Reject</button>
                            @error('rejection_reason', 'rejectResident')
                                <div class="invalid-feedback d-block w-100">{{ $message }}</div>
                            @enderror
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('modals')
    {{-- Screen 8: Add New Resident --}}
    <div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" action="{{ route('residents.store') }}" enctype="multipart/form-data" novalidate class="d-contents">
                    @csrf
                    <div class="modal-form-header">
                        <h2 class="h5 fw-bold" id="addResidentModalLabel">Add New Resident</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('residents._form-fields', ['bag' => 'createResident', 'resident' => null])
                    </div>
                    <div class="modal-form-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Resident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
