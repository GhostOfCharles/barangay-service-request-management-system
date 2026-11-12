{{-- Status tracker and what the resident should do next (FR-11, FR-12). Expects $serviceRequest with logs loaded. --}}
@php($latestLog = $serviceRequest->logs->sortByDesc('id')->first())
<x-status-tracker :service-request="$serviceRequest" />

@if ($serviceRequest->status === \App\Enums\RequestStatus::CashPayment)
    <div class="tracker-note">
        Ready for fulfillment. Present reference number <strong>{{ $serviceRequest->reference_no }}</strong> at the barangay hall
        and pay <strong><x-peso :amount="$serviceRequest->amountDue()" /></strong> in cash upon receiving your document.
    </div>
@else
    <div class="tracker-note is-plain">
        {{ $serviceRequest->status->residentInstruction() }}
        @if ($latestLog?->entry_type === \App\Enums\LogEntryType::Note)
            <br><span class="small">Latest note from staff: {{ $latestLog->note }}</span>
        @elseif ($latestLog)
            <br><span class="small text-secondary">Last updated {{ $latestLog->created_at->format('M j, Y g:i A') }}.</span>
        @endif
    </div>
@endif
