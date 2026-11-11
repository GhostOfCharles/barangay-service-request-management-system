{{-- Status tracker: the workflow steps with the current step highlighted (FR-04, FR-11). Expects logs loaded. --}}
@props(['serviceRequest', 'showDates' => true])

<ol {{ $attributes->class(['tracker']) }} aria-label="Request progress">
    @foreach ($serviceRequest->trackerSteps() as $step)
        <li class="tracker-step is-{{ $step['state'] }}" @if ($step['state'] === 'current') aria-current="step" @endif>
            <span class="tracker-dot" aria-hidden="true"></span>
            <span class="tracker-label">{{ $step['status']->label() }}</span>
            @if ($showDates && $step['reached_at'])
                <span class="tracker-date">{{ $step['reached_at']->format('M j, Y') }}</span>
            @endif
            @if ($step['state'] === 'current')
                <span class="visually-hidden">(current status)</span>
            @endif
        </li>
    @endforeach
</ol>
@unless ($serviceRequest->hasFee())
    <p class="small text-secondary mt-2 mb-0">No fee for this service, so the Cash Payment step is skipped.</p>
@endunless
