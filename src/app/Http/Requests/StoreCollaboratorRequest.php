<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollaboratorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Use the CollaboratorPolicy for creating new collaborators
        return $this->user()->can('create', \App\Models\Collaborator::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags($this->name ?? '')),
            'email' => strtolower(trim(strip_tags($this->email ?? ''))),
            'cpf' => preg_replace('/\D/', '', $this->cpf ?? ''), // Remove non-numeric characters
            'city' => trim(strip_tags($this->city ?? '')),
            'state' => strtoupper(trim(strip_tags($this->state ?? ''))),
            'user_id' => (int) ($this->user_id ?? 0),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:256',
            'email' => 'required|email|max:256|unique:collaborators,email',
            'cpf' => [
                'required',
                'string',
                'size:11',
                'unique:collaborators,cpf',
                'regex:/^\d{11}$/', // Exactly 11 digits
                function ($attribute, $value, $fail) {
                    if (!$this->isValidCpf($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                },
            ],
            'city' => 'required|string|max:256',
            'state' => 'required|string|max:256',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode ter mais de 256 caracteres.',
            
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está sendo usado por outro colaborador.',
            'email.max' => 'O email não pode ter mais de 256 caracteres.',
            
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.size' => 'O CPF deve ter exatamente 11 dígitos.',
            'cpf.unique' => 'Este CPF já está sendo usado por outro colaborador.',
            'cpf.regex' => 'O CPF deve conter apenas números.',
            
            'city.required' => 'A cidade é obrigatória.',
            'city.string' => 'A cidade deve ser um texto.',
            'city.max' => 'A cidade não pode ter mais de 256 caracteres.',
            
            'state.required' => 'O estado é obrigatório.',
            'state.string' => 'O estado deve ser um texto.',
            'state.max' => 'O estado não pode ter mais de 256 caracteres.',
            
            'user_id.required' => 'O usuário responsável é obrigatório.',
            'user_id.integer' => 'O ID do usuário deve ser um número.',
            'user_id.exists' => 'O usuário selecionado não existe.',
        ];
    }

    /**
     * Validate CPF using the standard algorithm
     */
    private function isValidCpf(string $cpf): bool
    {
        // Remove any non-numeric characters
        $cpf = preg_replace('/\D/', '', $cpf);

        // Check if it has 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check for known invalid CPFs (all same digits)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validate first digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        // Validate second digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }
}