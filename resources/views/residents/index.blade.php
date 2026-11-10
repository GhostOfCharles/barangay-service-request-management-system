@extends('layouts.app')

@section('title', 'Residents')
@section('reopenModal', $errors->createResident->any() ? 'addResidentModal' : '')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Residents</h1>
            <p class="page-subtitle mb-0">{{ $totalResidents }} registered {{ Str::plural('resident', $totalResidents) }}</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                <i class="bi bi-plus-lg"></i> Add New Resident
            </button>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('residents.index') }}" class="p-3 border-bottom d-flex flex-wrap gap-2" role="search">
            <div class="input-group flex-grow-1 flex-sm-grow-0" style="max-width: 360px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" name="search" value="{{ $search }}" class="form-control border-start-0"
                       placeholder="Search name or contact no..." aria-label="Search residents" autofocus>
            </div>
            <button type="submit" class="btn btn-outline-secondary">Search</button>
            @if (filled($search))
                <a href="{{ route('residents.index') }}" class="btn btn-link text-secondary">Clear</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Full Name</th>
                    <th class="d-none d-md-table-cell">Address</th>
                    <th class="d-none d-md-table-cell">Purok</th>
                    <th class="d-none d-sm-table-cell">Contact</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($residents as $resident)
                    <tr data-href="{{ route('residents.show', $resident) }}">
                        <td class="fw-medium">
                            {{ $resident->name }}
                            <div class="cell-sub d-md-none">{{ $resident->purok }}<span class="d-sm-none">@if ($resident->contact_number) · {{ $resident->contact_number }}@endif</span></div>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $resident->address }}</td>
                        <td class="d-none d-md-table-cell">{{ $resident->purok }}</td>
                        <td class="d-none d-sm-table-cell">{{ $resident->contact_number ?? '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('residents.show', $resident) }}" class="small">View/Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">
                            @if (filled($search))
                                No residents match "{{ $search }}". You can add them as a new resident.
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

    <div class="mt-3">{{ $residents->links() }}</div>
@endsection

@push('modals')
    <div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" action="{{ route('residents.store') }}" novalidate>
                    @csrf
                    <div class="modal-body p-4">
                        <h2 class="h6 fw-bold mb-3" id="addResidentModalLabel">Add New Resident</h2>
                        @include('residents._form-fields', ['bag' => 'createResident', 'resident' => null])
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Save Resident</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
