@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <div class="panel p-4 shadow-sm">
        <h2 class="h6 fw-bold mb-2">Forgot your password?</h2>
        <p class="small text-secondary">
            Please contact the system Administrator at the barangay office. The Administrator will reset your
            password and give you a temporary password. You will be asked to set a new password after you log in
            with it.
        </p>
        <a href="{{ route('login') }}" class="btn btn-success w-100">Back to Login</a>
    </div>
@endsection
