@extends('layouts.guest')

@section('title', 'Staff Login')

@section('content')
    <div class="panel panel-body">
        <h2 class="mb-3">Staff Login</h2>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}"
                       class="form-control @error('username') is-invalid @enderror"
                       autocomplete="username" required autofocus>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="current-password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-success w-100">Log In</button>
        </form>

        <div class="mt-3">
            <a href="{{ route('password.forgot') }}" class="small">Forgot Password?</a>
        </div>
    </div>
@endsection
