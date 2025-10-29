<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Use the UserPolicy with the specific user being updated
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim(strip_tags($this->name));
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim(strip_tags($this->email)));
        }

        if ($this->has('password')) {
            $data['password'] = trim($this->password);
        }

        if ($this->has('roles')) {
            $data['roles'] = array_map(function ($role) {
                return trim(strip_tags($role ?? ''));
            }, $this->roles ?? []);
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');
        $roleNames = Role::pluck('name')->toArray();

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$userId.'|max:255',
            'password' => 'sometimes|required|string|min:6|max:255',
            'roles' => 'sometimes|array',
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
            'password.max' => __('api.validation.password_max'),
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
