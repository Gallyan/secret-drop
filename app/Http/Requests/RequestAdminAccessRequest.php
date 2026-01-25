<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestAdminAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.max' => 'L\'adresse email ne doit pas dépasser 255 caractères.',
        ];
    }
}
