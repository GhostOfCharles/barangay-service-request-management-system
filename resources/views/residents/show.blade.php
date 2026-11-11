@extends('layouts.app')

@section('title', $resident->full_name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('residents.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to Residents</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">{{ $resident->full_name }}</h1>
            <p class="page-subtitle mb-0">
                {{ $resident->purok }} · {{ $serviceRequests->count() }} service {{ Str::plural('request', $serviceRequests->count()) }} ·
                <x-status-badge :status="$resident->verification_status" kind="verification" />
            </p>
        </div>
        @if ($resident->isVerified())
            <div class="page-actions">
                <a href="{{ route('service-requests.index', ['new' => 1, 'resident' => $resident->id]) }}" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> New Request
                </a>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-5 col-xl-4">
            <div class="panel mb-3">
                <div class="panel-header">Resident Information</div>
                <form method="POST" action="{{ route('residents.update', $resident) }}" enctype="multipart/form-data" class="p-3" novalidate>
                    @csrf
                    @method('PUT')
                    @include('residents._form-fields', ['bag' => 'updateResident', 'resident' => $resident])
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </form>
            </div>

            <div class="panel p-3">
                <h2 class="h6 fw-bold mb-2">Account and Verification</h2>
                <dl class="row small mb-0">
                    <dt class="col-5 text-secondary fw-normal">Portal account</dt>
                    <dd class="col-7">{{ $resident->user ? $resident->user->username : 'None (walk-in record)' }}</dd>
                    <dt class="col-5 text-secondary fw-normal">Verification</dt>
                    <dd class="col-7">
                        {{ $resident->verification_status->label() }}
                        @if ($resident->verified_at)
                            on {{ $resident->verified_at->format('M j, Y') }}{{ $resident->verifier ? ' by '.$resident->verifier->name : '' }}
                        @endif
                    </dd>
                    @if ($resident->rejection_reason)
                        <dt class="col-5 text-secondary fw-normal">Reason</dt>
                        <dd class="col-7">{{ $resident->rejection_reason }}</dd>
                    @endif
                    <dt class="col-5 text-secondary fw-normal">Valid ID</dt>
                    <dd class="col-7">
                        @if ($resident->valid_id_path)
                            <a href="{{ route('files.valid-id', $resident) }}" target="_blank" rel="noopener">View ID</a>
                        @else
                            Not uploaded
                        @endif
                    </dd>
                    @if ($resident->privacy_consent_at)
                        <dt class="col-5 text-secondary fw-normal">Privacy consent</dt>
                        <dd class="col-7 mb-0">Given {{ $resident->privacy_consent_at->format('M j, Y') }}</dd>
                    @endif
                </dl>
                @if ($resident->verification_status === \App\Enums\VerificationStatus::Pending)
                    <a href="{{ route('residents.index', ['verification' => 'pending', 'review' => $resident->id]) }}#verify" class="btn btn-outline-success btn-sm mt-3">Review ID</a>
                @endif
            </div>
        </div>

        <div class="col-lg-7 col-xl-8">
            <div class="panel">
                <div class="panel-header">Service Requests</div>
                <div class="table-responsive">
                    <table class="table table-app">
                        <thead>
                        <tr>
                            <th>Reference</th>
                            <th class="d-none d-md-table-cell">Service Type</th>
                            <th>Status</th>
                            <th class="d-none d-sm-table-cell text-end">Amount</th>
                            <th class="d-none d-sm-table-cell">Received</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($serviceRequests as $serviceRequest)
                            <tr data-href="{{ route('service-requests.show', $serviceRequest) }}">
                                <td>
                                    <span class="ref-no">{{ $serviceRequest->reference_no }}</span>
                                    <div class="cell-sub d-md-none">{{ $serviceRequest->serviceType->name }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $serviceRequest->serviceType->name }}</td>
                                <td><x-status-badge :status="$serviceRequest->status" /></td>
                                <td class="d-none d-sm-table-cell text-end"><x-peso :amount="$serviceRequest->fee_amount" /></td>
                                <td class="text-nowrap d-none d-sm-table-cell">{{ $serviceRequest->received_at->format('M j, Y') }}</td>
                                <td class="text-end text-nowrap"><a href="{{ route('service-requests.show', $serviceRequest) }}" class="small">View</a></td>
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
