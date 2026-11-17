<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Enums\VerificationStatus;
use App\Http\Requests\StoreResidentRequest;
use App\Http\Requests\UpdateResidentRequest;
use App\Models\ActivityLog;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function store(StoreResidentRequest $request): RedirectResponse
    {
        $validated = $request->residentAttributes();

        $resident = Resident::create([
            ...$validated,
            'valid_id_path' => $request->file('valid_id')?->store('valid-ids'),
            'verification_status' => VerificationStatus::Verified,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        ActivityLog::record($request->user(), ActivityAction::ResidentAdded,
            "Added {$resident->full_name} as a verified resident.", $resident,
            ActivityLog::differences([], self::snapshot($resident)));

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

    public function update(UpdateResidentRequest $request, Resident $resident): RedirectResponse
    {
        $validated = $request->residentAttributes();
        $before = self::snapshot($resident);

        if ($request->hasFile('valid_id')) {
            $validated['valid_id_path'] = $request->file('valid_id')->store('valid-ids');
        }

        $resident->update($validated);
        $resident->user?->update(['name' => $resident->full_name]);

        $changes = ActivityLog::differences($before, self::snapshot($resident));

        if ($request->hasFile('valid_id')) {
            $changes['Valid ID'] = [$before['Valid ID'], 'Replaced with a new upload'];
        }

        if ($changes) {
            ActivityLog::record($request->user(), ActivityAction::ResidentUpdated,
                "Edited the resident record of {$resident->full_name}.", $resident, $changes);
        }

        return redirect()->route('residents.show', $resident)
            ->with('success', 'Resident record has been updated.');
    }

    /**
     * The fields shown in the Activity Log, by label.
     *
     * @return array<string, ?string>
     */
    private static function snapshot(Resident $resident): array
    {
        return [
            'Full name' => $resident->full_name,
            'Address' => $resident->address,
            'Purok' => $resident->purok,
            'Contact number' => $resident->contact_number,
            'Valid ID' => $resident->valid_id_path ? 'On file' : 'None',
        ];
    }
}
