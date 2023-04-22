<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkCohortsRequest extends FormRequest
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
            'cohorts' => 'required|array',
            'cohorts.*.id' => 'required',
            'cohorts.*.name' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'cohorts.required' => 'Tienes que indicr, al menos, un grupo',
            'cohorts.*.id.required' => 'El ID del grupo es obligatorio',
            'cohorts.*.name.required' => 'El nombre del grupo es obligatorio'
        ];
    }
}
