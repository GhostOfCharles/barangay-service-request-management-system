<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reject a registration in Verify Residency (FR-09). The reason is shown to the resident at login.
 */
class RejectResidentRequest extends FormRequest
{
    /** @var string */
    protected $errorBag = 'rejectResident';

    /**
     * Personnel only; enforced by the role:personnel route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Please give the reason, so the resident knows what to fix.',
        ];
    }
}
