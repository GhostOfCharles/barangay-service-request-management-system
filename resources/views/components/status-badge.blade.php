@props(['status'])

<span class="status-badge status-{{ $status->value }}">{{ $status->label() }}</span>
