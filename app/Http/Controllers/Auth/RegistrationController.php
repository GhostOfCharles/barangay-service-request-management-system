<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Resident self-registration (FR-09, Screen 2). The account stays Pending until
 * Staff or the Administrator verify the uploaded valid ID.
 */
class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'puroks' => config('barangay.puroks'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['required', 'string', 'max:255'],
            'purok' => ['required', Rule::in(config('barangay.puroks'))],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', Password::min(8)],
            'valid_id' => ['required', File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb'))],
            'privacy_consent' => ['accepted'],
        ], [
            'contact_number.regex' => 'The contact number may only contain numbers, spaces, +, - and parentheses.',
            'valid_id.required' => 'Please upload a valid ID as proof of residency.',
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or PDF file.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
            'privacy_consent.accepted' => 'Please give your consent under the Data Privacy Act to create an account.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['full_name'],
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => UserRole::Resident,
                'status' => AccountStatus::Pending,
            ]);

            Resident::create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'address' => $validated['address'],
                'purok' => $validated['purok'],
                'contact_number' => $validated['contact_number'],
                'valid_id_path' => $request->file('valid_id')->store('valid-ids', 'local'),
                'verification_status' => VerificationStatus::Pending,
                'privacy_consent_at' => now(),
            ]);
        });

        return redirect()->route('login')->with(
            'status',
            'Registration received. Your account is activated after Staff or the Administrator verify your residency.',
        );
    }
}
