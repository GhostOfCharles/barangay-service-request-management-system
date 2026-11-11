@extends('layouts.guest')

@section('title', 'Resident Registration')
@section('width', '720px')
@section('footnote', 'Already registered? Log in once Staff or the Administrator verify your residency.')

@section('content')
    <div class="panel p-4 shadow-sm">
        <h2 class="h5 fw-bold mb-1">Resident Registration</h2>
        <p class="text-secondary small mb-3">Create your account to submit and track barangay service requests.</p>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required
                           class="form-control @error('full_name') is-invalid @enderror" placeholder="Last name, First name, M.I.">
                    @error('full_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" autocomplete="tel" inputmode="tel" required
                           class="form-control @error('contact_number') is-invalid @enderror" placeholder="09XX XXX XXXX">
                    @error('contact_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" required
                           class="form-control @error('address') is-invalid @enderror" placeholder="House no., Street">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="purok" class="form-label">Purok</label>
                    <select id="purok" name="purok" required class="form-select @error('purok') is-invalid @enderror">
                        <option value="">Select purok</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok }}" @selected(old('purok') === $purok)>{{ $purok }}</option>
                        @endforeach
                    </select>
                    @error('purok')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required
                           class="form-control @error('username') is-invalid @enderror" placeholder="Choose a username">
                    <div class="form-text">Letters, numbers, dashes and underscores only.</div>
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" required
                           class="form-control @error('password') is-invalid @enderror" placeholder="Create a password">
                    <div class="form-text">At least 8 characters.</div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label for="valid_id" class="form-label">Valid ID (proof of residency)</label>
                    <input type="file" id="valid_id" name="valid_id" accept=".jpg,.jpeg,.png,.pdf" required
                           class="form-control @error('valid_id') is-invalid @enderror">
                    <div class="form-text">JPG, PNG, or PDF only. Maximum 5 MB.</div>
                    @error('valid_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" @checked(old('privacy_consent'))
                               class="form-check-input @error('privacy_consent') is-invalid @enderror">
                        <label for="privacy_consent" class="form-check-label">
                            I consent to the collection and use of my personal information for barangay service processing,
                            in accordance with the Data Privacy Act of 2012 (RA 10173).
                        </label>
                        @error('privacy_consent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-success">Register</button>
                <span class="small text-secondary">Your account is activated after Staff or the Administrator verify your residency.</span>
            </div>
        </form>
    </div>

    <p class="text-center small mt-3 mb-0"><a href="{{ route('login') }}">Back to Log In</a></p>
@endsection
