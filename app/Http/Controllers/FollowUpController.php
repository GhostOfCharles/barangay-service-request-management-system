<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\RequestUpdateType;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    /**
     * Record a follow-up action and/or a status change as one history entry (FR-04, FR-05).
     */
    public function __invoke(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validateWithBag('addUpdate', [
            'action_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => [
                'nullable',
                Rule::enum(RequestStatus::class),
                Rule::notIn([$serviceRequest->status->value]),
            ],
            'note' => ['required_without:status', 'nullable', 'string', 'max:2000'],
        ], [
            'note.required_without' => 'Please describe the action taken, or choose a new status.',
            'status.not_in' => 'The request already has this status.',
            'action_date.before_or_equal' => 'The date cannot be in the future.',
        ]);

        $newStatus = RequestStatus::tryFrom($validated['status'] ?? '');

        if ($newStatus) {
            $serviceRequest->changeStatus(
                $newStatus,
                $request->user(),
                $validated['note'] ?? null,
                Carbon::parse($validated['action_date']),
            );

            return redirect()->route('service-requests.show', $serviceRequest)
                ->with('success', "Status updated to {$newStatus->label()}.");
        }

        $serviceRequest->updates()->create([
            'user_id' => $request->user()->id,
            'type' => RequestUpdateType::FollowUp,
            'action_date' => $validated['action_date'],
            'note' => $validated['note'],
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Follow-up has been added to the request history.');
    }
}
