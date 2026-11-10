@extends('layouts.app')

@section('title', $resident->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('residents.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to Residents</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">{{ $resident->name }}</h1>
            <p class="page-subtitle mb-0">{{ $resident->purok }} · {{ $serviceRequests->count() }} service {{ Str::plural('request', $serviceRequests->count()) }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('service-requests.index', ['new' => 1, 'resident' => $resident->id]) }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> New Request
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">Resident Information</div>
                <form method="POST" action="{{ route('residents.update', $resident) }}" class="p-3" novalidate>
                    @csrf
                    @method('PUT')
                    @include('residents._form-fields', ['bag' => 'updateResident', 'resident' => $resident])
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-header">Service Requests</div>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead>
                        <tr>
                            <th>Reference No.</th>
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
                                <td>
                                    <span class="ref-no">{{ $serviceRequest->reference_no }}</span>
                                    <div class="cell-sub d-md-none">{{ $serviceRequest->category->shortLabel() }} · {{ $serviceRequest->date_received->format('M j') }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $serviceRequest->category->shortLabel() }}</td>
                                <td class="text-secondary d-none d-xl-table-cell">{{ Str::limit($serviceRequest->description, 40) }}</td>
                                <td><x-status-badge :status="$serviceRequest->status" /></td>
                                <td class="text-nowrap d-none d-sm-table-cell">{{ $serviceRequest->date_received->format('M j, Y') }}</td>
                                <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small">View<span class="d-none d-sm-inline"> Details</span></a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No service requests for this resident yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
