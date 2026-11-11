@extends('layouts.app')

@section('title', 'Ticket Inbox')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Ticket Inbox</h1>
            <p class="page-subtitle mb-0">Answer resident tickets when time allows. No live chat.</p>
        </div>
        <div class="pill-group" role="group" aria-label="Filter tickets by status">
            @foreach (\App\Enums\TicketStatus::cases() as $tab)
                <a href="{{ route('tickets.index', ['status' => $tab->value]) }}" @class(['active' => $status === $tab])
                   @if ($status === $tab) aria-current="page" @endif>
                    {{ $tab->label() }}@if ($tab === \App\Enums\TicketStatus::Open) ({{ $counts[$tab->value] ?? 0 }})@endif
                </a>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="ticket-list">
                @forelse ($tickets as $ticket)
                    <a href="{{ route('tickets.index', ['status' => $status->value, 'ticket' => $ticket->id]) }}"
                       @class(['ticket-card', 'is-selected' => $selected?->is($ticket)])>
                        <div class="ticket-card-title">{{ $ticket->ticket_no }} · {{ $ticket->resident->full_name }}</div>
                        <div class="ticket-card-meta">
                            <x-status-badge :status="$ticket->status" kind="ticket" />
                            <span>{{ $ticket->subject }}@if ($ticket->serviceRequest) · {{ $ticket->serviceRequest->reference_no }}@endif</span>
                        </div>
                    </a>
                @empty
                    <div class="panel p-4 text-center text-secondary">No {{ Str::lower($status->label()) }} tickets.</div>
                @endforelse
            </div>
        </div>
        <div class="col-lg-7">
            @if ($selected)
                @include('tickets._thread', [
                    'ticket' => $selected,
                    'replyRoute' => route('tickets.reply', $selected),
                    'closeRoute' => route('tickets.close', $selected),
                    'note' => 'Sending a reply sets the ticket to Answered. A resident reply sets it back to Open.',
                ])
            @endif
        </div>
    </div>
@endsection
