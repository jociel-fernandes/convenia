<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Use the UserPolicy for creating new users
        return $this->user()->can('create', \App\Models\User::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags($this->name ?? '')),
            'email' => strtolower(trim(strip_tags($this->email ?? ''))),
            'password' => trim($this->password ?? ''),
            'password_confirmation' => trim($this->password_confirmation ?? ''),
            'roles' => array_map(function ($role) {
                return trim(strip_tags($role ?? ''));
            }, $this->roles ?? []),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleNames = Role::pluck('name')->toArray();

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|confirmed|max:255',
            'roles' => 'required|array',
            'roles.*' => 'string|in:'.implode(',', $roleNames),
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('api.validation.name_required'),
            'name.string' => __('api.validation.name_string'),
            'name.max' => __('api.validation.name_max'),
            'email.required' => __('api.validation.email_required'),
            'email.email' => __('api.validation.email_format'),
            'email.unique' => __('api.validation.email_unique'),
            'email.max' => __('api.validation.email_max'),
            'password.required' => __('api.validation.password_required'),
            'password.string' => __('api.validation.password_string'),
            'password.min' => __('api.validation.password_min'),
            'password.confirmed' => __('api.validation.password_confirmed'),
            'password.max' => __('api.validation.password_max'),
            'roles.required' => __('api.validation.roles_required'),
            'roles.array' => __('api.validation.roles_array'),
            'roles.*.in' => __('api.validation.roles_invalid'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('api.fields.name'),
            'email' => __('api.fields.email'),
            'password' => __('api.fields.password'),
            'roles' => __('api.fields.roles'),
        ];
    }
}
