@extends('layouts.app')

@section('title', 'Add User')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Back to User Management</a>
    </div>

    <div class="col-lg-5">
        <h1 class="page-title">Add User</h1>
        <p class="page-subtitle">A temporary password will be generated. The user must change it on first login.</p>

        <div class="panel p-4">
            <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
                @csrf
                @include('admin.users._form-fields')
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save User</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
