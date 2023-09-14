<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LendingGradesMessagingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Override this method to always send a JSON response when validation
     * error.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error en la validación',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'grades' => 'required|array|min:1',
            'grades.*' => 'integer'
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required' => 'Tienes que indicar, al menos, un curso al que enviar el e-mail',
            'grades.*.integer' => 'No se han indicado los cursos correctamente (deberían ser los identificadores del curso)'
        ];
    }
}
