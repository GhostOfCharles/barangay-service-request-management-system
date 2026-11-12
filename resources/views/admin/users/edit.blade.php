@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to User Management</a>
    </div>

    <div class="col-lg-5">
        <h1 class="page-title">Edit User</h1>
        <p class="page-subtitle">{{ $user->username }}</p>

        <div class="panel p-4">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
                @csrf
                @method('PUT')
                @include('admin.users._form-fields', ['user' => $user])

                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           @checked(old('is_active', $user->isActive()))>
                    <label for="is_active" class="form-check-label small">Account is active (can log in)</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
