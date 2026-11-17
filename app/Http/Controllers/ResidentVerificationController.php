<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\VerificationStatus;
use App\Models\ActivityLog;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Residency verification of self-registered residents (FR-09, Screen 7):
 * Approve & Activate lets the resident log in; Reject keeps the account inactive with a reason.
 */
class ResidentVerificationController extends Controller
{
    public function approve(Request $request, Resident $resident): RedirectResponse
    {
        $previous = $resident->verification_status;

        DB::transaction(function () use ($request, $resident) {
            $resident->update([
                'verification_status' => VerificationStatus::Verified,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $resident->user?->update(['status' => AccountStatus::Active]);
        });

        ActivityLog::record($request->user(), ActivityAction::ResidentApproved,
            "Approved the registration of {$resident->full_name} and activated the account.", $resident,
            ['Verification' => [$previous->label(), VerificationStatus::Verified->label()]]);

        return redirect()->route('residents.index', ['verification' => 'pending'])
            ->with('success', "{$resident->full_name} is verified. The account is now active.");
    }

    public function reject(Request $request, Resident $resident): RedirectResponse
    {
        $validated = $request->validateWithBag('rejectResident', [
            'rejection_reason' => ['required', 'string', 'max:255'],
        ], [
            'rejection_reason.required' => 'Please give the reason, so the resident knows what to fix.',
        ]);

        $previous = $resident->verification_status;

        DB::transaction(function () use ($request, $resident, $validated) {
            $resident->update([
                'verification_status' => VerificationStatus::Rejected,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $resident->user?->update(['status' => AccountStatus::Rejected]);
        });

        ActivityLog::record($request->user(), ActivityAction::ResidentRejected,
            "Rejected the registration of {$resident->full_name}.", $resident, [
                'Verification' => [$previous->label(), VerificationStatus::Rejected->label()],
                'Reason' => [null, $validated['rejection_reason']],
            ]);

        return redirect()->route('residents.index', ['verification' => 'pending'])
            ->with('success', "{$resident->full_name}'s registration was rejected. The account stays inactive.");
    }
}
