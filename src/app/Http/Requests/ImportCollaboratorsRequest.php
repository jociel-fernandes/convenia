<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ImportCollaboratorsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Use the CollaboratorPolicy for importing collaborators
        return $this->user()->can('create', \App\Models\Collaborator::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240', // 10MB max
            ],
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
            'file.required' => 'O arquivo CSV é obrigatório.',
            'file.file' => 'Deve ser um arquivo válido.',
            'file.mimes' => 'O arquivo deve ser do tipo CSV (.csv ou .txt).',
            'file.max' => 'O arquivo não pode ser maior que 10MB.',
            
            'has_header.boolean' => 'O campo "tem cabeçalho" deve ser verdadeiro ou falso.',
            'delimiter.in' => 'O delimitador deve ser vírgula (,), ponto e vírgula (;) ou pipe (|).',
            'encoding.in' => 'A codificação deve ser UTF-8 ou ISO-8859-1.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Apenas definir valores padrão sem validação extra
        $this->merge([
            'has_header' => $this->boolean('has_header', true),
            'delimiter' => $this->input('delimiter', ','),
            'encoding' => $this->input('encoding', 'utf-8'),
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'arquivo CSV',
            'has_header' => 'cabeçalho',
            'delimiter' => 'delimitador',
            'encoding' => 'codificação',
        ];
    }
}