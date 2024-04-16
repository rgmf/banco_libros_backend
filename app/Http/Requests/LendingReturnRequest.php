<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LendingReturnRequest extends FormRequest
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
            'student_id' => 'required',
            'book_copies' => 'required|array',
            'book_copies.*.id' => 'required',
            'book_copies.*.comment' => 'nullable|string',
            'book_copies.*.status_id' => 'required',
            'book_copies.*.observations_id' => 'array'
        ];
    }

    public function messages(): array
    {
        return [
            'student_id' => 'Se necesita el estudiante que tiene prestado estos libros',
            'book_copies' => 'Se necesita, al menos, un libro que devolver',
            'book_copies.*.id' => 'El identificador de los libros a devolver es obligatorio',
            'book_copies.*.status_id' => 'El estado de los libros a devolver es obligatorio',
            'book_copies.*.observations_id' => 'Se necesita un array con los identificadores de las observaciones'
        ];
    }
}
