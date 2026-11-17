<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterResidentRequest;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function store(RegisterResidentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
                'valid_id_path' => $request->file('valid_id')->store('valid-ids'),
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
