<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateDispatchStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statusCode' => [
                'required',
                'string',
                Rule::in(['assigned', 'en_route', 'on_site', 'closed']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'statusCode.required' => 'O campo statusCode é obrigatório',
            'statusCode.in' => 'O statusCode deve ser um dos valores: assigned, en_route, on_site, closed',
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

