<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request: Criar Despacho
 */
class CreateDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resourceCode' => ['required', 'string', 'max:50', 'regex:/^[A-Z]{2,3}-\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'resourceCode.required' => 'O campo resourceCode é obrigatório',
            'resourceCode.regex' => 'O formato do resourceCode é inválido. Use o padrão ABT-12 ou UR-05',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

