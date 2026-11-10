@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <div class="panel panel-body">
        <h2 class="mb-2">Forgot your password?</h2>
        <p class="mb-3">
            Please contact the system Administrator at the barangay office. The Administrator will reset your
            password and give you a temporary password. You will be asked to set a new password after you log in
            with it.
        </p>
        <a href="{{ route('login') }}" class="btn btn-success w-100">Back to Login</a>
    </div>
@endsection
