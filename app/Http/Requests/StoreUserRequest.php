<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a Staff or Administrator account (User Management). Resident accounts come only from registration.
 */
class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::personnelRoles())],
        ];
    }
}
