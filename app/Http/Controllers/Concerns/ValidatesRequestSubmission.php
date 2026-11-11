<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

/**
 * Validation shared by the resident submission form (Screen 4) and the staff-assisted form (Screen 11):
 * an active service type, a purpose, and every required document from that service's checklist (FR-10).
 */
trait ValidatesRequestSubmission
{
    /**
     * @param  array<string, mixed>  $extraRules
     * @return array{0: array<string, mixed>, 1: array<int, UploadedFile>}
     */
    protected function validateSubmission(Request $request, string $errorBag, array $extraRules = []): array
    {
        $serviceType = ServiceType::with('documentRequirements')
            ->where('is_active', true)
            ->find($request->input('service_type_id'));

        $rules = [
            ...$extraRules,
            'service_type_id' => ['required'],
            'purpose' => ['required', 'string', 'max:2000'],
        ];
        $messages = [
            'service_type_id.required' => 'Please select a service type.',
            'purpose.required' => 'Please enter the purpose or details of the request.',
        ];

        foreach ($serviceType?->documentRequirements ?? [] as $requirement) {
            $key = "documents.{$requirement->id}";
            $rules[$key] = [
                $requirement->is_required ? 'required' : 'nullable',
                File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb')),
            ];
            $messages["{$key}.required"] = "Please attach the {$requirement->document_name}.";
            $messages["{$key}.mimes"] = "The {$requirement->document_name} must be a JPG, PNG, or PDF file.";
            $messages["{$key}.max"] = "The {$requirement->document_name} must not be larger than 5 MB.";
            $messages["{$key}.uploaded"] = "The {$requirement->document_name} could not be uploaded. Try a smaller file.";
        }

        $validated = $request->validateWithBag($errorBag, $rules, $messages);

        if (! $serviceType) {
            throw ValidationException::withMessages(['service_type_id' => 'Please select an available service type.'])
                ->errorBag($errorBag);
        }

        /** @var array<int, UploadedFile> $files */
        $files = array_filter($request->file('documents', []));

        return [$validated, $files];
    }

    /**
     * The active service types with their fees and checklists, for the forms' page script.
     *
     * @return list<array<string, mixed>>
     */
    protected function serviceTypeOptions(): array
    {
        return ServiceType::with('documentRequirements')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceType $serviceType): array => $serviceType->toFormOption())
            ->values()
            ->all();
    }
}
