@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to User Management</a>
    </div>

    <div class="col-lg-6">
        <div class="page-head">
            <div>
                <h1 class="page-title">Edit User</h1>
                <p class="page-subtitle mb-0">{{ $user->username }}</p>
            </div>
        </div>

        <div class="panel panel-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" novalidate>
                @csrf
                @method('PUT')
                @include('admin.users._form-fields', ['user' => $user])

                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                           @checked(old('is_active', $user->is_active))>
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
