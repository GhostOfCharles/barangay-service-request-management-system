<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Enums\RequestCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceTypeRequest;
use App\Models\ActivityLog;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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

    public function store(ServiceTypeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $serviceType = DB::transaction(function () use ($validated) {
            $serviceType = ServiceType::create(Arr::except($validated, 'documents'));
            $this->syncChecklist($serviceType, $validated['documents'] ?? []);

            return $serviceType;
        });

        ActivityLog::record($request->user(), ActivityAction::ServiceTypeCreated,
            "Added the service type {$serviceType->name}.", $serviceType,
            ActivityLog::differences([], self::snapshot($serviceType)));

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', "{$serviceType->name} has been added.");
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType): RedirectResponse
    {
        $validated = $request->validated();
        $before = self::snapshot($serviceType);

        DB::transaction(function () use ($serviceType, $validated) {
            $serviceType->update(Arr::except($validated, 'documents'));
            $this->syncChecklist($serviceType, $validated['documents'] ?? []);
        });

        $changes = ActivityLog::differences($before, self::snapshot($serviceType));

        if ($changes) {
            ActivityLog::record($request->user(), ActivityAction::ServiceTypeUpdated,
                "Edited the service type {$serviceType->name}.", $serviceType, $changes);
        }

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', "{$serviceType->name} has been saved. The new fee and checklist appear on the submission form.");
    }

    /**
     * Deactivate hides the service from new requests; existing requests keep it. Activate brings it back.
     */
    public function toggle(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $serviceType->update(['is_active' => ! $serviceType->is_active]);

        ActivityLog::record($request->user(),
            $serviceType->is_active ? ActivityAction::ServiceTypeActivated : ActivityAction::ServiceTypeDeactivated,
            ($serviceType->is_active ? 'Activated' : 'Deactivated')." the service type {$serviceType->name}.", $serviceType,
            ['Availability' => $serviceType->is_active ? ['Inactive', 'Active'] : ['Active', 'Inactive']]);

        return redirect()->route('admin.service-types.index', ['edit' => $serviceType->id])
            ->with('success', $serviceType->is_active
                ? "{$serviceType->name} is active again."
                : "{$serviceType->name} is deactivated. Residents can no longer choose it.");
    }

    /**
     * The fields shown in the Activity Log, by label. The checklist reads like "Valid ID (required), Cedula (optional)".
     *
     * @return array<string, ?string>
     */
    private static function snapshot(ServiceType $serviceType): array
    {
        $checklist = $serviceType->documentRequirements()->orderBy('id')->get()
            ->map(fn ($requirement) => $requirement->document_name.($requirement->is_required ? ' (required)' : ' (optional)'))
            ->implode(', ');

        return [
            'Name' => $serviceType->name,
            'Category' => $serviceType->category->label(),
            'Fee' => '₱'.number_format((float) $serviceType->fee, 2),
            'Checklist' => $checklist ?: 'None',
        ];
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
