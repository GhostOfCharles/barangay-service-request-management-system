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
 * Reject a registration with a reason (FR-09): the resident becomes Rejected, the login account stays
 * inactive (the reason is shown at login), and the decision is written to the Activity Log.
 */
class RejectResidentAction
{
    public function handle(Resident $resident, User $verifier, string $reason): void
    {
        $previous = $resident->verification_status;

        DB::transaction(function () use ($resident, $verifier, $reason): void {
            $resident->update([
                'verification_status' => VerificationStatus::Rejected,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $resident->user?->update(['status' => AccountStatus::Rejected]);
        });

        ActivityLog::record($verifier, ActivityAction::ResidentRejected,
            "Rejected the registration of {$resident->full_name}.", $resident, [
                'Verification' => [$previous->label(), VerificationStatus::Rejected->label()],
                'Reason' => [null, $reason],
            ]);
    }
}
