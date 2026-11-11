<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * Resident records for Staff and the Administrator (FR-03, FR-06, Screens 7 and 8).
 */
class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        $verification = VerificationStatus::tryFrom((string) $request->query('verification'));
        $search = $request->query('search');

        $reviewing = $request->filled('review')
            ? Resident::with('user')->find($request->query('review'))
            : null;

        return view('residents.index', [
            'residents' => Resident::search($search)
                ->when($verification, fn ($query) => $query->where('verification_status', $verification))
                ->orderBy('full_name')
                ->paginate(15)
                ->withQueryString(),
            'totalResidents' => Resident::count(),
            'search' => $search,
            'verification' => $verification,
            'reviewing' => $reviewing,
            'puroks' => config('barangay.puroks'),
        ]);
    }

    /**
     * Add New Resident (Screen 8). A resident added in person by Staff is recorded as verified.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateResident($request, 'createResident');

        $resident = Resident::create([
            ...$validated,
            'valid_id_path' => $request->file('valid_id')?->store('valid-ids', 'local'),
            'verification_status' => VerificationStatus::Verified,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return redirect()->route('residents.index')
            ->with('success', "Resident {$resident->full_name} has been saved.");
    }

    public function show(Resident $resident): View
    {
        return view('residents.show', [
            'resident' => $resident->load(['user', 'verifier']),
            'serviceRequests' => $resident->serviceRequests()->with('serviceType')->latest('received_at')->latest('id')->get(),
            'puroks' => config('barangay.puroks'),
        ]);
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $this->validateResident($request, 'updateResident');

        if ($request->hasFile('valid_id')) {
            $validated['valid_id_path'] = $request->file('valid_id')->store('valid-ids', 'local');
        }

        $resident->update($validated);
        $resident->user?->update(['name' => $resident->full_name]);

        return redirect()->route('residents.show', $resident)
            ->with('success', 'Resident record has been updated.');
    }

    /**
     * @return array{full_name: string, address: string, purok: string, contact_number: ?string}
     */
    private function validateResident(Request $request, string $errorBag): array
    {
        $validated = $request->validateWithBag($errorBag, [
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'purok' => ['required', Rule::in(config('barangay.puroks'))],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'valid_id' => ['nullable', File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb'))],
        ], [
            'contact_number.regex' => 'The contact number may only contain numbers, spaces, +, - and parentheses.',
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or PDF file.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
        ]);

        unset($validated['valid_id']);

        return $validated;
    }
}
