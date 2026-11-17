<?php

namespace App\Http\Controllers;

use App\Actions\ApproveResidentAction;
use App\Actions\RejectResidentAction;
use App\Http\Requests\RejectResidentRequest;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Residency verification of self-registered residents (FR-09, Screen 7):
 * Approve & Activate lets the resident log in; Reject keeps the account inactive with a reason.
 */
class ResidentVerificationController extends Controller
{
    public function approve(Request $request, Resident $resident, ApproveResidentAction $approveResident): RedirectResponse
    {
        $approveResident->handle($resident, $request->user());

        return redirect()->route('residents.index', ['verification' => 'pending'])
            ->with('success', "{$resident->full_name} is verified. The account is now active.");
    }

    public function reject(RejectResidentRequest $request, Resident $resident, RejectResidentAction $rejectResident): RedirectResponse
    {
        $rejectResident->handle($resident, $request->user(), $request->validated('rejection_reason'));

        return redirect()->route('residents.index', ['verification' => 'pending'])
            ->with('success', "{$resident->full_name}'s registration was rejected. The account stays inactive.");
    }
}
