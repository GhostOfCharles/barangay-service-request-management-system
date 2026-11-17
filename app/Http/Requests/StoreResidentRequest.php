<?php

namespace App\Http\Requests;

use App\Rules\PhilippineMobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Add New Resident (Screen 8), filled in by Staff or the Administrator for a walk-in resident.
 * Errors go to the "createResident" bag so the Add Resident dialog reopens with them.
 */
class StoreResidentRequest extends FormRequest
{
    /** @var string */
    protected $errorBag = 'createResident';

    /**
     * Personnel only; enforced by the role:personnel route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['contact_number' => PhilippineMobileNumber::normalize($this->input('contact_number'))]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'purok' => ['required', Rule::in(config('barangay.puroks'))],
            'contact_number' => ['nullable', 'string', new PhilippineMobileNumber],
            'valid_id' => ['nullable', File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or PDF file.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
        ];
    }

    /**
     * The resident fields to save; the uploaded ID is stored separately.
     *
     * @return array{full_name: string, address: string, purok: string, contact_number: ?string}
     */
    public function residentAttributes(): array
    {
        return $this->safe()->only(['full_name', 'address', 'purok', 'contact_number']);
    }
}
