<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The status workflow on Request Details (FR-04, FR-12): Update Status for the next step,
 * and Mark Completed for the Administrator only.
 */
class RequestStatusController extends Controller
{
    public function update(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validateWithBag('updateStatus', [
            'status' => ['required', Rule::enum(RequestStatus::class)],
        ]);

        $this->moveOrFail($request, $serviceRequest, RequestStatus::from($validated['status']));

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', "Status updated to {$serviceRequest->status->label()}.");
    }

    /**
     * After the resident pays in cash and receives the document (or for a ₱0.00 service once Approved).
     */
    public function complete(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->moveOrFail($request, $serviceRequest, RequestStatus::Completed);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', "{$serviceRequest->reference_no} is marked Completed.");
    }

    private function moveOrFail(Request $request, ServiceRequest $serviceRequest, RequestStatus $status): void
    {
        if (! $serviceRequest->canMoveTo($status, $request->user())) {
            $message = $status === RequestStatus::Completed && ! $request->user()->isAdministrator()
                ? 'Only the Administrator can set Completed.'
                : "That is not the next step. {$serviceRequest->reference_no} can only move to "
                    .($serviceRequest->nextStatus()?->label() ?? 'no further status').'.';

            throw ValidationException::withMessages(['status' => $message])->errorBag('updateStatus');
        }

        $serviceRequest->moveTo($status, $request->user());
    }
}
