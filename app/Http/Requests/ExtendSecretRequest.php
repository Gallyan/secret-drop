<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendSecretRequest extends FormRequest
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
            'days' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.required' => 'Le nombre de jours est requis.',
            'days.integer' => 'Le nombre de jours doit être un entier.',
            'days.min' => 'Le nombre de jours doit être au moins 1.',
            'days.max' => 'Le nombre de jours ne peut pas dépasser 30.',
        ];
    }
}
