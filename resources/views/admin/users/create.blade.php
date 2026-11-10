@extends('layouts.app')

@section('title', 'Add User')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to User Management</a>
    </div>

    <div class="col-lg-6">
        <div class="page-head">
            <div>
                <h1 class="page-title">Add User</h1>
                <p class="page-subtitle mb-0">A temporary password will be generated. The user must change it on first login.</p>
            </div>
        </div>

        <div class="panel panel-body">
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
