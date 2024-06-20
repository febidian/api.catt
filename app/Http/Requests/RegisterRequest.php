<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3'],
            'email' => ['required', 'unique:users,email', 'email'],
            'password' => ['bail', 'required', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'min:6']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => "failed",
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
