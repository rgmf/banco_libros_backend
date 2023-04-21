<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkStudentsRequest extends FormRequest
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
            'students' => 'required|array',
            'students.*.nia' => 'required',
            'students.*.name' => 'required',
            'students.*.lastname1' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'students.required' => 'Tienes que indicar, al menos, un estudiante',
            'students.*.nia.required' => 'El NIA es obligatorio',
            'students.*.name.required' => 'El nombre es obligatorio',
            'students.*.lastname1.required' => 'El primer apellido es obligatorio'
        ];
    }
}
