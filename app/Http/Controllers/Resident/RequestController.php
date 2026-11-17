<?php

namespace App\Http\Controllers\Resident;

use App\Actions\SubmitServiceRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitServiceRequest;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The resident's Dynamic Submission Form (FR-02, FR-10, Screen 4).
 */
class RequestController extends Controller
{
    public function create(): View
    {
        return view('resident.requests.create', [
            'serviceTypeOptions' => ServiceType::formOptions(),
        ]);
    }

    public function store(SubmitServiceRequest $request, SubmitServiceRequestAction $submitRequest): RedirectResponse
    {
        $serviceRequest = $submitRequest->handle([
            ...$request->submissionAttributes(),
            'resident_id' => $request->user()->resident->id,
        ], $request->user(), $request->documents());

        return redirect()->route('resident.dashboard', ['track' => $serviceRequest->id])
            ->with('success', "Request submitted. Your reference number is {$serviceRequest->reference_no}.");
    }
}
