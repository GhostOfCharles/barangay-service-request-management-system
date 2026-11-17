<?php

namespace App\Http\Requests;

use App\Rules\PhilippineMobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;

/**
 * Resident self-registration with a valid ID and Data Privacy Act consent (FR-09, Screen 2).
 */
class RegisterResidentRequest extends FormRequest
{
    /**
     * Open to guests; the route is limited by the guest middleware and throttled.
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
            'contact_number' => ['required', 'string', new PhilippineMobileNumber],
            'address' => ['required', 'string', 'max:255'],
            'purok' => ['required', Rule::in(config('barangay.puroks'))],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', Password::defaults()],
            'valid_id' => ['required', File::types(config('barangay.upload_mimes'))->max(config('barangay.upload_max_kb'))],
            'privacy_consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'valid_id.required' => 'Please upload a valid ID as proof of residency.',
            'valid_id.mimes' => 'The valid ID must be a JPG, PNG, or PDF file.',
            'valid_id.max' => 'The valid ID must not be larger than 5 MB.',
            'privacy_consent.accepted' => 'Please give your consent under the Data Privacy Act to create an account.',
        ];
    }
}
