@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle mb-0">Administrator and Staff accounts that can log in to the system</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.create') }}" class="btn btn-success"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add User</a>
        </div>
    </div>

    @if ($temporary = session('temporaryPassword'))
        <div class="alert alert-warning d-block" role="status">
            <div class="fw-semibold mb-1"><i class="bi bi-key" aria-hidden="true"></i> Temporary password for <span class="font-monospace">{{ $temporary['username'] }}</span></div>
            <div class="secret mb-1">{{ $temporary['password'] }}</div>
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
                        <td class="row-name">
                            {{ $user->name }}
                            <div class="cell-sub d-md-none"><span class="font-monospace">{{ $user->username }}</span><span class="d-sm-none">{{ $user->role->label() }}</span></div>
                        </td>
                        <td class="font-monospace small d-none d-md-table-cell">{{ $user->username }}</td>
                        <td class="d-none d-lg-table-cell">{{ $user->position ?? 'None' }}</td>
                        <td class="d-none d-sm-table-cell">{{ $user->role->label() }}</td>
                        <td>
                            @if ($user->is_active)
                                <span class="chip chip-active">Active</span>
                            @else
                                <span class="chip chip-inactive">Inactive</span>
                            @endif
                            @if ($user->must_change_password)
                                <span class="chip chip-warning">Temporary password</span>
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
