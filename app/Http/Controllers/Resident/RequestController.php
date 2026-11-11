<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Concerns\ValidatesRequestSubmission;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The resident's Dynamic Submission Form (FR-02, FR-10, Screen 4).
 */
class RequestController extends Controller
{
    use ValidatesRequestSubmission;

    public function create(): View
    {
        return view('resident.requests.create', [
            'serviceTypeOptions' => $this->serviceTypeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$validated, $files] = $this->validateSubmission($request, 'submitRequest');

        $serviceRequest = ServiceRequest::submit([
            ...$validated,
            'resident_id' => $request->user()->resident->id,
        ], $request->user(), $files);

        return redirect()->route('resident.dashboard', ['track' => $serviceRequest->id])
            ->with('success', "Request submitted. Your reference number is {$serviceRequest->reference_no}.");
    }
}
