<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Concerns\ValidatesRequestSubmission;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Service requests for Staff and the Administrator (FR-02, FR-06, Screens 9 to 11).
 */
class ServiceRequestController extends Controller
{
    use ValidatesRequestSubmission;

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'service_type', 'status', 'date_from', 'date_to']);
        $residents = Resident::where('verification_status', VerificationStatus::Verified)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'purok', 'contact_number']);

        return view('service-requests.index', [
            'serviceRequests' => ServiceRequest::with(['resident', 'serviceType'])
                ->filter($filters)
                ->latest('received_at')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'totalRequests' => ServiceRequest::count(),
            'filters' => $filters,
            'statuses' => RequestStatus::cases(),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'serviceTypeOptions' => $this->serviceTypeOptions(),
            'residentOptions' => $residents->map(fn (Resident $resident): array => [
                'id' => $resident->id,
                'label' => "{$resident->full_name} ({$resident->purok})",
                'contact' => $resident->contact_number,
                'search' => Str::lower("{$resident->full_name} {$resident->purok} {$resident->contact_number}"),
            ])->values(),
            'residents' => $residents,
        ]);
    }

    /**
     * Staff-assisted request for a walk-in resident (Screen 11); the reference number is generated on save.
     */
    public function store(Request $request): RedirectResponse
    {
        [$validated, $files] = $this->validateSubmission($request, 'createRequest', [
            'resident_id' => ['required', 'integer', 'exists:residents,id'],
            'received_at' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $serviceRequest = ServiceRequest::submit($validated, $request->user(), $files);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', "Service request {$serviceRequest->reference_no} has been saved.");
    }

    /**
     * Request Details (Screen 10): documents, status tracker, status update, and the request log.
     */
    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $serviceRequest->load(['resident', 'serviceType', 'documents', 'logs.user']);
        $nextStatus = $serviceRequest->nextStatus();

        return view('service-requests.show', [
            'serviceRequest' => $serviceRequest,
            'nextStatus' => $nextStatus,
            'staffCanUpdate' => $nextStatus !== null && $nextStatus !== RequestStatus::Completed,
            'canComplete' => $nextStatus === RequestStatus::Completed && $request->user()->isAdministrator(),
            'logEntries' => $serviceRequest->logs->sortByDesc('id')->values(),
        ]);
    }
}
