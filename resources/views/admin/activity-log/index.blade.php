@extends('layouts.app')

@section('title', 'Activity Log')

@php
    $activeFilterCount = count(array_filter(\Illuminate\Support\Arr::only($filters, ['user', 'action', 'date_from', 'date_to'])));
    $filteredUser = $users->firstWhere('id', (int) $filters['user']);
    $filteredAction = \App\Enums\ActivityAction::tryFrom((string) $filters['action']);
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Activity Log</h1>
            <p class="page-subtitle mb-0">Every change made by Staff and the Administrator. Entries cannot be edited or deleted.</p>
        </div>
        <div class="page-actions is-pair d-flex gap-2">
            <button type="button" class="btn btn-outline-success bg-white" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            <a href="{{ route('admin.activity-log.export', array_filter($filters)) }}" class="btn btn-outline-success bg-white">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- Shown only on paper: what this printout covers. --}}
    <p class="d-none d-print-block small">
        Printed {{ now()->format('M j, Y g:i A') }} by {{ auth()->user()->name }}.
        @if ($filteredUser) User: {{ $filteredUser->name }}. @endif
        @if ($filteredAction) Action: {{ $filteredAction->label() }}. @endif
        @if ($filters['date_from'] || $filters['date_to']) Dates: {{ $filters['date_from'] ?: 'start' }} to {{ $filters['date_to'] ?: 'today' }}. @endif
        @if ($filters['search']) Search: “{{ $filters['search'] }}”. @endif
    </p>

    <form method="GET" action="{{ route('admin.activity-log.index') }}" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3" role="search">
        <div class="input-group filter-search" style="max-width: 280px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control border-start-0"
                   placeholder="Reference no., name, or text…" aria-label="Search the activity log" enterkeyhint="search">
        </div>

        <button type="button" class="btn btn-outline-secondary bg-white filter-toggle d-md-none" data-bs-toggle="offcanvas" data-bs-target="#logFilters"
                aria-controls="logFilters">
            <i class="bi bi-sliders"></i> Filters
            @if ($activeFilterCount)
                <span class="filter-count">{{ $activeFilterCount }}<span class="visually-hidden"> active</span></span>
            @endif
        </button>

        <div class="offcanvas-md offcanvas-bottom filter-sheet" tabindex="-1" id="logFilters" aria-labelledby="logFiltersLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h6 fw-bold" id="logFiltersLabel">Filter the activity log</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#logFilters" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <select name="user" class="form-select" style="max-width: 200px;" aria-label="Filter by user">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $filters['user'] === (string) $user->id)>{{ $user->name }} ({{ $user->username }})</option>
                    @endforeach
                </select>

                <select name="action" class="form-select" style="max-width: 220px;" aria-label="Filter by action">
                    <option value="">All actions</option>
                    @foreach ($actionGroups as $area => $actions)
                        <optgroup label="{{ $area }}">
                            @foreach ($actions as $action)
                                <option value="{{ $action->value }}" @selected($filters['action'] === $action->value)>{{ $action->label() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <div class="date-range d-flex align-items-center gap-1">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control" aria-label="From date" title="From date">
                    <span class="text-secondary small">to</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control" aria-label="To date" title="To date">
                </div>

                <button type="submit" class="btn btn-outline-secondary bg-white d-none d-md-inline-block">Search</button>
                <button type="submit" class="btn btn-success d-md-none">Show results</button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.activity-log.index') }}" class="btn btn-link text-secondary">Clear<span class="d-md-none"> filters</span></a>
                @endif
            </div>
        </div>
    </form>

    {{-- New entries appear every few seconds (resources/js/live-poll.js); filters are left alone. --}}
    @fragment('activity-results')
    <div data-live="activity-results">
        <div class="panel">
            <div class="table-responsive">
                <table class="table table-app activity-table">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th class="d-none d-sm-table-cell">User</th>
                        <th class="d-none d-md-table-cell">Action</th>
                        <th>What changed</th>
                        <th class="d-none d-xl-table-cell">IP address</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($entries as $entry)
                        @php($subjectUrl = $entry->subjectUrl())
                        <tr>
                            <td class="text-nowrap">
                                {{ $entry->created_at->format('M j, Y') }}
                                <div class="cell-sub">{{ $entry->created_at->format('g:i:s A') }}</div>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <span class="fw-medium">{{ $entry->user_name }}</span>
                                <div class="cell-sub">{{ $entry->user_role->label() }}</div>
                            </td>
                            <td class="d-none d-md-table-cell"><span class="action-tag">{{ $entry->action->label() }}</span></td>
                            <td class="activity-details">
                                <span class="action-tag d-md-none mb-1">{{ $entry->action->label() }}</span>
                                <div class="cell-sub d-sm-none">By {{ $entry->user_name }} ({{ $entry->user_role->label() }})</div>
                                <div>
                                    @if ($entry->subject_label)
                                        @if ($subjectUrl)
                                            <a href="{{ $subjectUrl }}" class="fw-semibold">{{ $entry->subject_label }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $entry->subject_label }}</span>
                                        @endif
                                        ·
                                    @endif
                                    {{ $entry->description }}
                                </div>
                                @if ($entry->changes)
                                    <ul class="change-list">
                                        @foreach ($entry->changes as $field => [$before, $after])
                                            <li>
                                                <span class="change-field">{{ $field }}:</span>
                                                @if ($before !== null)
                                                    <del>{{ $before }}</del> <span aria-hidden="true">→</span><span class="visually-hidden">changed to</span>
                                                @endif
                                                <ins>{{ $after ?? '(blank)' }}</ins>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="cell-sub d-xl-none">IP {{ $entry->ip_address ?? 'unknown' }}</div>
                            </td>
                            <td class="d-none d-xl-table-cell font-monospace small">{{ $entry->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                @if (array_filter($filters))
                                    No activity matches these filters. Clear a filter or widen the dates.
                                @else
                                    No activity recorded yet. Changes made by Staff and the Administrator will appear here.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
    @endfragment
@endsection
