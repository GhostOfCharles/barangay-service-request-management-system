{{-- A status pill for a request, ticket, verification, or account status. --}}
@props(['status', 'kind' => 'request'])

<span {{ $attributes->class(['status-badge', "status-{$kind}-{$status->value}"]) }}>{{ $status->label() }}</span>
