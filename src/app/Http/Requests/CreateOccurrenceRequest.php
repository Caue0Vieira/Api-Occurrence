<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request: Criar Ocorrência
 *
 * Valida dados para criação de ocorrência.
 */
class CreateOccurrenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autenticação já feita no middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'externalId' => ['required', 'string', 'max:100'],
            'type' => [
                'required',
                'string',
                'in:incendio_urbano,resgate_veicular,atendimento_pre_hospitalar,salvamento_aquatico,falso_chamado,vazamento_gas,queda_arvore,incendio_florestal',
            ],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'reportedAt' => ['required', 'date', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'externalId.required' => 'O campo externalId é obrigatório',
            'type.required' => 'O campo type é obrigatório',
            'type.in' => 'O tipo de ocorrência informado não é válido',
            'description.required' => 'O campo description é obrigatório',
            'description.min' => 'A descrição deve ter no mínimo 10 caracteres',
            'reportedAt.required' => 'O campo reportedAt é obrigatório',
            'reportedAt.date_format' => 'O formato da data reportedAt deve ser ISO 8601 (ex: 2026-02-01T14:32:00-03:00)',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'message' => 'The given data was invalid',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

