@extends('layouts.app')

@section('title', $serviceRequest->reference_no)

@php
    use App\Enums\LogEntryType;
    use App\Enums\RequestStatus;
@endphp

@section('content')
    <div class="mb-3">
        <a href="{{ route('service-requests.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to Service Requests</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Request Details — {{ $serviceRequest->reference_no }}</h1>
            <p class="page-subtitle mb-0">
                {{ $serviceRequest->serviceType->name }} ·
                <a href="{{ route('residents.show', $serviceRequest->resident) }}">{{ $serviceRequest->resident->full_name }}</a> ({{ $serviceRequest->resident->purok }}) ·
                Received {{ $serviceRequest->received_at->format('M j, Y') }} ·
                Fee <x-peso :amount="$serviceRequest->fee_amount" />
            </p>
        </div>
        <x-status-badge :status="$serviceRequest->status" class="fs-6" />
    </div>

    <div class="panel p-3 mb-3">
        <x-status-tracker :service-request="$serviceRequest" />
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            {{-- Update Status: Staff move the request one step at a time up to Cash Payment (FR-04). --}}
            <div class="panel p-3 mb-3">
                <h2 class="h6 fw-bold mb-3">Update Status</h2>

                @if ($staffCanUpdate)
                    <form method="POST" action="{{ route('service-requests.status', $serviceRequest) }}" class="d-flex flex-wrap gap-2" novalidate>
                        @csrf
                        @method('PATCH')
                        <label for="status" class="visually-hidden">Next status</label>
                        <select id="status" name="status" class="form-select flex-grow-1 @error('status', 'updateStatus') is-invalid @enderror" style="max-width: 260px;">
                            <option value="{{ $nextStatus->value }}">{{ $nextStatus->label() }}</option>
                        </select>
                        <button type="submit" class="btn btn-success">Update Status</button>
                    </form>
                @elseif ($serviceRequest->status === RequestStatus::Completed)
                    <p class="mb-0">This request is completed{{ $serviceRequest->completed_at ? ' on '.$serviceRequest->completed_at->format('M j, Y') : '' }}.</p>
                @else
                    <p class="mb-2">The next step is <strong>Completed</strong>, which only the Administrator can set{{ $serviceRequest->hasFee() ? ' after the resident pays in cash and receives the document' : '' }}.</p>
                @endif

                @error('status', 'updateStatus')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror

                @if ($serviceRequest->status !== RequestStatus::Completed)
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        @if ($canComplete)
                            <form method="POST" action="{{ route('service-requests.complete', $serviceRequest) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-success">Mark Completed</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-light border" disabled aria-describedby="complete-hint">Mark Completed</button>
                        @endif
                        <span class="small text-secondary" id="complete-hint">Administrator only</span>
                    </div>
                @endif

                <p class="small text-secondary mt-3 mb-0">Staff may set statuses up to Cash Payment. Services with a ₱0.00 fee skip Cash Payment.</p>
            </div>

            <div class="panel p-3">
                <h2 class="h6 fw-bold mb-3">Submitted Documents</h2>
                <ul class="checklist">
                    @forelse ($serviceRequest->documents as $document)
                        <li class="checklist-row is-attached">
                            <span class="checklist-box" aria-hidden="true">✓</span>
                            <span class="checklist-name">{{ $document->document_name }}</span>
                            <span class="checklist-file d-none d-sm-inline">{{ $document->original_name }} · {{ $document->readableSize() }}</span>
                            <a href="{{ route('files.document', $document) }}" target="_blank" rel="noopener" class="small fw-semibold">View</a>
                        </li>
                    @empty
                        <li class="text-secondary small">No documents were attached to this request.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            {{-- Request log: every status change and follow-up note, newest first (FR-04, FR-05). --}}
            <div class="panel p-3">
                <h2 class="h6 fw-bold mb-3">Request Log &amp; Follow-up Notes</h2>
                <ul class="request-log">
                    @foreach ($logEntries as $entry)
                        <li>
                            @if ($entry->entry_type === LogEntryType::Note)
                                <div><strong>Note:</strong> {{ $entry->note }}</div>
                                <div class="request-log-time">{{ $entry->user->name }} · {{ $entry->created_at->format('M j, Y · g:i A') }}</div>
                            @else
                                <div><strong>{{ $entry->new_status->label() }}</strong> — {{ $entry->user->name }}</div>
                                <div class="request-log-time">{{ $entry->created_at->format('M j, Y · g:i A') }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('service-requests.notes', $serviceRequest) }}" novalidate>
                    @csrf
                    <label for="note" class="visually-hidden">Follow-up note</label>
                    <textarea id="note" name="note" rows="2" class="form-control @error('note', 'addNote') is-invalid @enderror"
                              placeholder="Add a follow-up note…">{{ $errors->addNote->any() ? old('note') : '' }}</textarea>
                    @error('note', 'addNote')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-outline-success mt-2">Add Note</button>
                </form>
            </div>
        </div>
    </div>
@endsection
