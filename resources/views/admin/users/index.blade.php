@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle mb-0">Administrator and Staff accounts that can log in to the system</p>
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
                            @if ($user->is_active)
                                <span class="badge text-bg-success">Active</span>
                            @else
                                <span class="badge text-bg-secondary">Inactive</span>
                            @endif
                            @if ($user->must_change_password)
                                <span class="badge text-bg-warning">Temporary password</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.edit', $user) }}" class="small me-2 text-nowrap">Edit</a>
                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="d-inline"
                                  onsubmit="return confirm('Reset the password for {{ $user->username }}? A temporary password will be issued.');">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0 small text-danger">Reset Password</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
