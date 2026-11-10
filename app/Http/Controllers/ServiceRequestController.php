<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    /**
     * Searchable and filterable request list (FR-06).
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'category', 'date_from', 'date_to']);
        $residents = Resident::orderBy('name')->get(['id', 'name', 'purok', 'contact_number']);

        return view('service-requests.index', [
            'serviceRequests' => ServiceRequest::with('resident')
                ->filter($filters)
                ->latest('date_received')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'totalRequests' => ServiceRequest::count(),
            'filters' => $filters,
            'statuses' => RequestStatus::cases(),
            'categories' => RequestCategory::cases(),
            'documentStatuses' => DocumentStatus::cases(),
            'residents' => $residents,
            'residentOptions' => $residents->map(fn (Resident $resident): array => [
                'id' => $resident->id,
                'label' => "{$resident->name} ({$resident->purok})",
                'contact' => $resident->contact_number,
                'search' => Str::lower("{$resident->name} {$resident->purok} {$resident->contact_number}"),
            ]),
        ]);
    }

    /**
     * Create a request under the selected category with a unique reference (FR-02).
     * The resident is either chosen from the list or added in the same form.
     */
    public function store(Request $request): RedirectResponse
    {
        $isNewResident = $request->boolean('new_resident');
        $chosenResident = Rule::excludeIf($isNewResident);
        $newResident = Rule::excludeIf(! $isNewResident);

        $validated = $request->validateWithBag('createRequest', [
            'resident_id' => [$chosenResident, 'required', 'integer', 'exists:residents,id'],
            'resident.name' => [$newResident, 'required', 'string', 'max:255'],
            'resident.address' => [$newResident, 'required', 'string', 'max:255'],
            'resident.contact_number' => [$newResident, 'nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'resident.purok' => [$newResident, 'required', 'string', 'max:50'],
            ...$this->requestDetailRules(),
        ], [
            'resident_id.required' => 'Please choose a resident from the list, or add a new resident.',
            'resident_id.exists' => 'Please choose a resident from the list, or add a new resident.',
            'resident.name.required' => 'Please enter the new resident\'s name.',
            'resident.address.required' => 'Please enter the new resident\'s address.',
            'resident.purok.required' => 'Please enter the new resident\'s purok.',
            'resident.contact_number.regex' => 'The contact number may only contain numbers, spaces, +, - and parentheses.',
            ...$this->requestDetailMessages(),
        ]);

        $serviceRequest = DB::transaction(function () use ($isNewResident, $validated, $request) {
            $residentId = $isNewResident
                ? Resident::create($validated['resident'])->id
                : $validated['resident_id'];

            return ServiceRequest::record(
                [...Arr::except($validated, ['resident', 'resident_id']), 'resident_id' => $residentId],
                $request->user(),
            );
        });

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', "Service request {$serviceRequest->reference_no} has been saved.");
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        $serviceRequest->load(['resident', 'creator', 'updates' => fn ($query) => $query->with('user')->latest('action_date')->latest('id')]);

        return view('service-requests.show', [
            'serviceRequest' => $serviceRequest,
            'statuses' => RequestStatus::cases(),
            'categories' => RequestCategory::cases(),
            'documentStatuses' => DocumentStatus::cases(),
        ]);
    }

    /**
     * Correct the request details (category, description, document check, date received).
     */
    public function update(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->update(
            $request->validateWithBag('updateRequest', $this->requestDetailRules(), $this->requestDetailMessages()),
        );

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Request details have been updated.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function requestDetailRules(): array
    {
        return [
            'category' => ['required', Rule::enum(RequestCategory::class)],
            'description' => ['required', 'string', 'max:2000'],
            'document_status' => ['required', Rule::enum(DocumentStatus::class)],
            'date_received' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function requestDetailMessages(): array
    {
        return [
            'category.required' => 'Please select a service category.',
            'document_status.required' => 'Please select the document-check status.',
            'date_received.before_or_equal' => 'The date received cannot be in the future.',
        ];
    }
}
