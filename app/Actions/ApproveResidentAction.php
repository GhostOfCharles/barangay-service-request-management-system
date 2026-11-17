<?php

namespace App\Actions;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\VerificationStatus;
use App\Models\ActivityLog;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Approve & Activate a registration (FR-09): the resident becomes Verified, the login account becomes
 * active, and the decision is written to the Activity Log.
 */
class ApproveResidentAction
{
    public function handle(Resident $resident, User $verifier): void
    {
        $previous = $resident->verification_status;

        DB::transaction(function () use ($resident, $verifier): void {
            $resident->update([
                'verification_status' => VerificationStatus::Verified,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $resident->user?->update(['status' => AccountStatus::Active]);
        });

        ActivityLog::record($verifier, ActivityAction::ResidentApproved,
            "Approved the registration of {$resident->full_name} and activated the account.", $resident,
            ['Verification' => [$previous->label(), VerificationStatus::Verified->label()]]);
    }
}
