<?php

namespace App\Http\Requests;

use App\Enums\RequestCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add or edit a service type with its fee and required-document checklist (FR-14, Screen 13).
 */
class ServiceTypeRequest extends FormRequest
{
    /**
     * Administrator only; enforced by the can:administer route middleware.
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
            'name' => ['required', 'string', 'max:255', Rule::unique('service_types', 'name')->ignore($this->route('serviceType'))],
            'category' => ['required', Rule::enum(RequestCategory::class)],
            'fee' => ['required', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'],
            'documents' => ['array'],
            'documents.*.id' => ['nullable', 'integer'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.required' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fee.min' => 'The fee cannot be negative. Use 0.00 for a free service.',
            'fee.decimal' => 'Enter the fee in pesos with up to two decimals, for example 50.00.',
        ];
    }
}
