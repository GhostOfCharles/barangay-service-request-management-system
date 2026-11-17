<?php

namespace App\Http\Requests;

use App\Models\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * The Dynamic Submission Form (FR-02, FR-10, Screen 4): the rules follow the chosen service type's
 * checklist, one upload per required document (JPG, PNG, or PDF up to 5 MB).
 */
class SubmitServiceRequest extends FormRequest
{
    /** @var string */
    protected $errorBag = 'submitRequest';

    private ?ServiceType $serviceType = null;

    private bool $serviceTypeLoaded = false;

    /**
     * Verified residents only; enforced by the role:resident route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The chosen service type, if it exists and is still offered.
     */
    public function serviceType(): ?ServiceType
    {
        if (! $this->serviceTypeLoaded) {
            $this->serviceType = ServiceType::with('documentRequirements')
                ->where('is_active', true)
                ->find($this->input('service_type_id'));
            $this->serviceTypeLoaded = true;
        }

        return $this->serviceType;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'service_type_id' => ['required'],
            'purpose' => ['required', 'string', 'max:2000'],
        ];

        foreach ($this->serviceType()?->documentRequirements ?? [] as $requirement) {
            $rules["documents.{$requirement->id}"] = [
                $requirement->is_required ? 'required' : 'nullable',
                File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb')),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'service_type_id.required' => 'Please select a service type.',
            'purpose.required' => 'Please enter the purpose or details of the request.',
        ];

        foreach ($this->serviceType()?->documentRequirements ?? [] as $requirement) {
            $key = "documents.{$requirement->id}";
            $messages["{$key}.required"] = "Please attach the {$requirement->document_name}.";
            $messages["{$key}.mimes"] = "The {$requirement->document_name} must be a JPG, PNG, or PDF file.";
            $messages["{$key}.max"] = "The {$requirement->document_name} must not be larger than 5 MB.";
            $messages["{$key}.uploaded"] = "The {$requirement->document_name} could not be uploaded. Try a smaller file.";
        }

        return $messages;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('service_type_id') && ! $this->serviceType()) {
                    $validator->errors()->add('service_type_id', 'Please select an available service type.');
                }
            },
        ];
    }

    /**
     * The request fields to save.
     *
     * @return array<string, mixed>
     */
    public function submissionAttributes(): array
    {
        return $this->safe()->only(['service_type_id', 'purpose']);
    }

    /**
     * The attached files, keyed by document requirement id.
     *
     * @return array<int, UploadedFile>
     */
    public function documents(): array
    {
        return array_filter($this->file('documents', []));
    }
}
