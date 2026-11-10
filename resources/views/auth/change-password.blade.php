@extends($isForced ? 'layouts.guest' : 'layouts.app')

@section('title', 'Change Password')

@section('content')
    <div class="@unless ($isForced) col-lg-5 @endunless">
        @unless ($isForced)
            <h1 class="page-title">Change Password</h1>
            <p class="page-subtitle">Update the password you use to log in.</p>
        @endunless

        <div class="panel p-4 shadow-sm">
            @if ($isForced)
                <h2 class="h6 fw-bold mb-2">Set a new password</h2>
                <div class="alert alert-warning small py-2">
                    You logged in with a temporary password. Please choose a new password to continue.
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="current_password" class="form-label small">{{ $isForced ? 'Temporary password' : 'Current password' }}</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                           class="form-control @error('current_password') is-invalid @enderror" required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small">New password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    <div class="form-text">At least 8 characters.</div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label small">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                           class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success w-100">Save Password</button>
            </form>

            @if ($isForced)
                <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm text-secondary">Log Out</button>
                </form>
            @endif
        </div>
    </div>
@endsection
