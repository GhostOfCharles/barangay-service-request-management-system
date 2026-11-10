@extends('layouts.app')

@section('title', $serviceRequest->reference_no)
@section('reopenModal', $errors->updateRequest->any() ? 'editRequestModal' : '')

@section('content')
    <div class="mb-3">
        <a href="{{ route('service-requests.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to Service Requests</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">
                Request <span class="ref-no fs-6">{{ $serviceRequest->reference_no }}</span>
                <x-status-badge :status="$serviceRequest->status" />
            </h1>
            <p class="page-subtitle mb-0">{{ $serviceRequest->category->label() }} · Received {{ $serviceRequest->date_received->format('M j, Y') }}</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-outline-secondary bg-white" data-bs-toggle="modal" data-bs-target="#editRequestModal">
                <i class="bi bi-pencil"></i> Edit Details
            </button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel mb-3">
                <div class="panel-header">Request Information</div>
                <dl class="row mb-0 p-3 small">
                    <dt class="col-5 text-secondary fw-normal">Resident</dt>
                    <dd class="col-7"><a href="{{ route('residents.show', $serviceRequest->resident) }}" class="fw-medium">{{ $serviceRequest->resident->name }}</a></dd>

                    <dt class="col-5 text-secondary fw-normal">Address</dt>
                    <dd class="col-7">{{ $serviceRequest->resident->address }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Contact</dt>
                    <dd class="col-7">{{ $serviceRequest->resident->contact_number ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Category</dt>
                    <dd class="col-7">{{ $serviceRequest->category->label() }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Description</dt>
                    <dd class="col-7" style="white-space: pre-line;">{{ $serviceRequest->description }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Document Check</dt>
                    <dd class="col-7">{{ $serviceRequest->document_status->label() }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Date Received</dt>
                    <dd class="col-7">{{ $serviceRequest->date_received->format('M j, Y') }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Recorded By</dt>
                    <dd class="col-7 mb-0">{{ $serviceRequest->creator->name }}</dd>
                </dl>
            </div>

        </div>

        <div class="col-lg-7 d-flex flex-column">
            {{-- On phones the history comes first, then the form; on desktop the form is on top. --}}
            <div class="panel mb-3 order-2 order-lg-1" id="add-update">
                <div class="panel-header">Add Follow-up / Update Status</div>
                <form method="POST" action="{{ route('service-requests.follow-ups', $serviceRequest) }}" class="p-3" novalidate>
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="action_date" class="form-label small">Date</label>
                            <input type="date" id="action_date" name="action_date" max="{{ today()->toDateString() }}"
                                   value="{{ old('action_date', today()->toDateString()) }}"
                                   class="form-control @error('action_date', 'addUpdate') is-invalid @enderror" required>
                            @error('action_date', 'addUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="update-status" class="form-label small">Change status to <span class="text-secondary">(optional)</span></label>
                            <select id="update-status" name="status" class="form-select @error('status', 'addUpdate') is-invalid @enderror">
                                <option value="">Keep as {{ $serviceRequest->status->label() }}</option>
                                @foreach ($statuses as $status)
                                    @continue($status === $serviceRequest->status)
                                    <option value="{{ $status->value }}" @selected(old('status') === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            @error('status', 'addUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="update-note" class="form-label small">Action taken / note</label>
                        <textarea id="update-note" name="note" rows="3" class="form-control @error('note', 'addUpdate') is-invalid @enderror"
                                  placeholder="e.g. Called the resident to submit a valid ID.">{{ old('note') }}</textarea>
                        <div class="form-text">Required when the status stays the same.</div>
                        @error('note', 'addUpdate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success">Save Update</button>
                </form>
            </div>

            <div class="panel mb-3 order-1 order-lg-2">
                <div class="panel-header">Request History</div>
                <div class="p-3">
                    <ul class="timeline">
                        @foreach ($serviceRequest->updates as $update)
                            <li>
                                <div class="small text-secondary">
                                    {{ $update->action_date->format('M j, Y') }} · {{ $update->user->name }}
                                </div>
                                <div class="fw-medium">
                                    @switch($update->type)
                                        @case(\App\Enums\RequestUpdateType::Created)
                                            Request recorded as <x-status-badge :status="$update->to_status" />
                                            @break
                                        @case(\App\Enums\RequestUpdateType::StatusChange)
                                            Status changed from <x-status-badge :status="$update->from_status" /> to <x-status-badge :status="$update->to_status" />
                                            @break
                                        @default
                                            Follow-up action
                                    @endswitch
                                </div>
                                @if ($update->note && $update->type !== \App\Enums\RequestUpdateType::Created)
                                    <div class="small mt-1" style="white-space: pre-line;">{{ $update->note }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="editRequestModal" tabindex="-1" aria-labelledby="editRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" action="{{ route('service-requests.update', $serviceRequest) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h2 class="h6 fw-bold mb-3" id="editRequestModalLabel">Edit Request Details</h2>
                        <p class="small text-secondary">Resident: <strong>{{ $serviceRequest->resident->name }}</strong></p>
                        @include('service-requests._form-fields', ['bag' => 'updateRequest', 'serviceRequest' => $serviceRequest])
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
