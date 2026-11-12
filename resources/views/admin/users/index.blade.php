@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle mb-0">Administrator, Staff, and Resident accounts that can log in. Staff and Administrator accounts are added here; residents register themselves.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.create') }}" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add User</a>
        </div>
    </div>

    @if ($temporary = session('temporaryPassword'))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-key"></i> Temporary password for <span class="font-monospace">{{ $temporary['username'] }}</span></div>
            <div class="font-monospace fs-5 mb-1">{{ $temporary['password'] }}</div>
            <div class="small">Give this to the user privately. It is shown only once, and they must change it after logging in.</div>
        </div>
    @endif

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-app">
                <thead>
                <tr>
                    <th>Name</th>
                    <th class="d-none d-md-table-cell">Username</th>
                    <th class="d-none d-lg-table-cell">Position</th>
                    <th class="d-none d-sm-table-cell">Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="fw-medium">
                            {{ $user->name }}
                            <div class="cell-sub d-md-none"><span class="font-monospace">{{ $user->username }}</span><span class="d-sm-none"> · {{ $user->role->label() }}</span></div>
                        </td>
                        <td class="font-monospace small d-none d-md-table-cell">{{ $user->username }}</td>
                        <td class="d-none d-lg-table-cell">{{ $user->position ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell">{{ $user->role->label() }}</td>
                        <td>
                            <x-status-badge :status="$user->status" kind="account" />
                            @if ($user->must_change_password)
                                <span class="badge text-bg-warning">Temporary password</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" id="reset-password-{{ $user->id }}" class="d-none"
                                  onsubmit="return confirm('Reset the password for {{ $user->username }}? A temporary password will be issued.');">
                                @csrf
                            </form>

                            {{-- Wider screens: the actions as links. --}}
                            <span class="d-none d-sm-inline">
                                @if ($user->isResident())
                                    @if ($user->resident)
                                        <a href="{{ route('residents.show', $user->resident) }}" class="small me-2">Resident record</a>
                                    @endif
                                @else
                                    <a href="{{ route('admin.users.edit', $user) }}" class="small me-2">Edit</a>
                                @endif
                                <button type="submit" form="reset-password-{{ $user->id }}" class="btn btn-link btn-sm p-0 small text-danger">Reset Password</button>
                            </span>

                            {{-- Phones: the same actions in a menu, so the row fits the screen. --}}
                            <div class="dropdown d-sm-none">
                                <button type="button" class="btn btn-light border btn-sm px-2" data-bs-toggle="dropdown" aria-expanded="false"
                                        aria-label="Actions for {{ $user->name }}">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if ($user->isResident())
                                        @if ($user->resident)
                                            <li><a class="dropdown-item py-2" href="{{ route('residents.show', $user->resident) }}">Resident record</a></li>
                                        @endif
                                    @else
                                        <li><a class="dropdown-item py-2" href="{{ route('admin.users.edit', $user) }}">Edit</a></li>
                                    @endif
                                    <li><button type="submit" form="reset-password-{{ $user->id }}" class="dropdown-item py-2 text-danger">Reset Password</button></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
