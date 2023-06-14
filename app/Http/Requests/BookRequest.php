<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookRequest extends FormRequest
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
            'isbn' => 'string|required|max:13',
            'title' => 'string|required',
            'author' => 'string|required',
            'publisher' => 'string|required',
            'volumes' => 'numeric|required|min:1'
        ];
    }

    public function messages()
    {
        return [
            'isbn' => 'El ISBN es obligatorio y tiene que ser de un máximo de 13 caracteres',
            'title' => 'El título es obligatorio',
            'author' => 'El autor es obligatorio',
            'publisher' => 'La editorial es obligatoria',
            'volumes' => 'El número de vólumenes de un libro es obligatorio y, al menos, 1'
        ];
    }
}
