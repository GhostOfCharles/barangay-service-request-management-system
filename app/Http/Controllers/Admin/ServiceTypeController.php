<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestCategory;
use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Service Types, Fees and Checklists, Administrator only (FR-14, Screen 13).
 */
class ServiceTypeController extends Controller
{
    public function index(Request $request): View
    {
        $editing = $request->filled('edit')
            ? ServiceType::with('documentRequirements')->find($request->query('edit'))
            : null;

        return view('admin.service-types.index', [
            'serviceTypes' => ServiceType::withCount('documentRequirements')->orderByDesc('is_active')->orderBy('name')->get(),
            'editing' => $editing,
            'creating' => ! $editing && $request->boolean('new'),
            'categories' => RequestCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateServiceType($request);

        $serviceType = DB::transaction(function () use ($validated) {
            $serviceType = ServiceType::create(Arr::except($validated, 'documents'));
            $this->syncChecklist($serviceType, $validated['documents'] ?? []);

            return $serviceType;
        });

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', "{$serviceType->name} has been added.");
    }

    public function update(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $validated = $this->validateServiceType($request, $serviceType);

        DB::transaction(function () use ($serviceType, $validated) {
            $serviceType->update(Arr::except($validated, 'documents'));
            $this->syncChecklist($serviceType, $validated['documents'] ?? []);
        });

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', "{$serviceType->name} has been saved. The new fee and checklist appear on the submission form.");
    }

    /**
     * Deactivate hides the service from new requests; existing requests keep it. Activate brings it back.
     */
    public function toggle(ServiceType $serviceType): RedirectResponse
    {
        $serviceType->update(['is_active' => ! $serviceType->is_active]);

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', $serviceType->is_active
                ? "{$serviceType->name} is active again."
                : "{$serviceType->name} is deactivated. Residents can no longer choose it.");
    }

    /**
     * @return array{name: string, category: string, fee: string, documents?: list<array{id?: ?string, name: ?string, required?: ?string}>}
     */
    private function validateServiceType(Request $request, ?ServiceType $serviceType = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('service_types', 'name')->ignore($serviceType)],
            'category' => ['required', Rule::enum(RequestCategory::class)],
            'fee' => ['required', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'],
            'documents' => ['array'],
            'documents.*.id' => ['nullable', 'integer'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.required' => ['nullable', 'boolean'],
        ], [
            'fee.min' => 'The fee cannot be negative. Use 0.00 for a free service.',
            'fee.decimal' => 'Enter the fee in pesos with up to two decimals, for example 50.00.',
        ]);
    }

    /**
     * Save the checklist rows: update the kept rows, add new ones, and remove the rows the Administrator deleted.
     *
     * @param  list<array{id?: ?string, name: ?string, required?: ?string}>  $rows
     */
    private function syncChecklist(ServiceType $serviceType, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $attributes = ['document_name' => $name, 'is_required' => (bool) ($row['required'] ?? false)];
            $existing = filled($row['id'] ?? null) ? $serviceType->documentRequirements()->find($row['id']) : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $serviceType->documentRequirements()->create($attributes)->id;
            }
        }

        $serviceType->documentRequirements()->whereNotIn('id', $keptIds)->delete();
    }
}
