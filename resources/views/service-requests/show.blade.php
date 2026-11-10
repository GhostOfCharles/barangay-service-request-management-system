@extends('layouts.app')

@section('title', $serviceRequest->reference_no)
@section('reopenModal', $errors->updateRequest->any() ? 'editRequestModal' : '')

@section('content')
    <div class="mb-3">
        <a href="{{ route('service-requests.index') }}" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Service Requests</a>
    </div>

    <div class="page-head">
        <div>
            <h1 class="page-title">Request {{ $serviceRequest->reference_no }}</h1>
            <p class="page-subtitle mb-0">
                <x-status-badge :status="$serviceRequest->status" />
                {{ $serviceRequest->category->label() }}, received {{ $serviceRequest->date_received->format('M j, Y') }}
            </p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRequestModal">
                <i class="bi bi-pencil" aria-hidden="true"></i> Edit Details
            </button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel mb-3">
                <div class="panel-header">Request Information</div>
                <dl class="facts panel-body">
                    <dt>Resident</dt>
                    <dd><a href="{{ route('residents.show', $serviceRequest->resident) }}" >{{ $serviceRequest->resident->name }}</a></dd>

                    <dt>Address</dt>
                    <dd>{{ $serviceRequest->resident->address }}</dd>

                    <dt>Contact</dt>
                    <dd>{{ $serviceRequest->resident->contact_number ?? 'None' }}</dd>

                    <dt>Category</dt>
                    <dd>{{ $serviceRequest->category->label() }}</dd>

                    <dt>Description</dt>
                    <dd class="multiline">{{ $serviceRequest->description }}</dd>

                    <dt>Document Check</dt>
                    <dd>{{ $serviceRequest->document_status->label() }}</dd>

                    <dt>Date Received</dt>
                    <dd>{{ $serviceRequest->date_received->format('M j, Y') }}</dd>

                    <dt>Recorded By</dt>
                    <dd>{{ $serviceRequest->creator->name }}</dd>
                </dl>
            </div>

        </div>

        <div class="col-lg-7 d-flex flex-column">
            {{-- On phones the history comes first, then the form; on desktop the form is on top. --}}
            <div class="panel mb-3 order-2 order-lg-1" id="add-update">
                <div class="panel-header">Add Follow-up / Update Status</div>
                <form method="POST" action="{{ route('service-requests.follow-ups', $serviceRequest) }}" class="panel-body" novalidate>
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="action_date" class="form-label">Date</label>
                            <input type="date" id="action_date" name="action_date" max="{{ today()->toDateString() }}"
                                   value="{{ old('action_date', today()->toDateString()) }}"
                                   class="form-control @error('action_date', 'addUpdate') is-invalid @enderror" required>
                            @error('action_date', 'addUpdate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="update-status" class="form-label">Change status to <span class="text-secondary">(optional)</span></label>
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
                        <label for="update-note" class="form-label">Action taken / note</label>
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
                <div class="panel-body">
                    <ul class="timeline">
                        @foreach ($serviceRequest->updates as $update)
                            <li>
                                <div class="timeline-meta">
                                    <time datetime="{{ $update->action_date->toDateString() }}">{{ $update->action_date->format('M j, Y') }}</time>, by {{ $update->user->name }}
                                </div>
                                <div class="timeline-event">
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
                                    <div class="timeline-note">{{ $update->note }}</div>
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
                        <h2 class="mb-3" id="editRequestModalLabel">Edit Request Details</h2>
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
