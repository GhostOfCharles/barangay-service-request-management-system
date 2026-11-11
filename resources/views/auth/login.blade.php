@extends('layouts.guest')

@section('title', 'Log In')
@section('footnote', 'Administrator, Staff, and Resident accounts use this same login page. Access depends on the account role.')

@section('content')
    <div class="panel p-4 shadow-sm">
        <h2 class="h6 fw-bold mb-3">Log In</h2>

        @if (session('status'))
            <div class="alert alert-success small py-2">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label small">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}"
                       class="form-control @error('username') is-invalid @enderror"
                       placeholder="Enter your username" autocomplete="username" required autofocus>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Enter your password" autocomplete="current-password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success w-100">Log In</button>
        </form>

        <p class="text-center mt-3 mb-1">
            New resident? <a href="{{ route('register') }}" class="fw-semibold">Register an account</a>
        </p>
        <div class="text-center">
            <a href="{{ route('password.forgot') }}" class="small text-secondary">Forgot Password?</a>
        </div>
    </div>
@endsection
