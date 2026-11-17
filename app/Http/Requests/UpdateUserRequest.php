<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edit a Staff or Administrator account. An Administrator cannot remove their own Administrator role
 * or deactivate their own account, so the system always keeps at least one working Administrator.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Administrator only (can:administer route middleware). Resident accounts are not edited here,
     * so they are treated as not found, as on the edit page.
     */
    public function authorize(): bool
    {
        abort_if($this->route('user')->isResident(), 404);

        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($this->route('user'))],
            'position' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::personnelRoles())],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isSelf = $this->user()->is($this->route('user'));

                if ($isSelf && ($this->input('role') !== UserRole::Administrator->value || ! $this->boolean('is_active'))) {
                    $validator->errors()->add('role', 'You cannot remove your own Administrator role or deactivate your own account.');
                }
            },
        ];
    }
}
