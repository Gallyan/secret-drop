<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:text,file'],
            'ciphertext' => ['required_if:type,text', 'string'],
            'cipher_meta' => ['required', 'array'],
            'cipher_meta.alg' => ['required', 'string'],
            'cipher_meta.iv' => ['required', 'string'],
            'cipher_meta.version' => ['required', 'integer', 'min:1'],
            'cipher_meta.salt' => ['nullable', 'string'],
            'cipher_meta.kdf' => ['nullable', 'string'],
            'expiration' => ['required', 'in:1h,1d,7d,30d'],
            'usage_unique' => ['boolean'],
            'max_views' => ['nullable', 'integer', 'min:1', 'max:100'],
            'creator_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de secret est requis.',
            'type.in' => 'Le type doit être "text" ou "file".',
            'ciphertext.required_if' => 'Le texte chiffré est requis pour un secret texte.',
            'cipher_meta.required' => 'Les métadonnées de chiffrement sont requises.',
            'cipher_meta.alg.required' => 'L\'algorithme de chiffrement est requis.',
            'cipher_meta.iv.required' => 'Le vecteur d\'initialisation est requis.',
            'cipher_meta.version.required' => 'La version du chiffrement est requise.',
            'expiration.required' => 'La durée d\'expiration est requise.',
            'expiration.in' => 'La durée d\'expiration est invalide.',
            'max_views.min' => 'Le nombre de lectures doit être au moins 1.',
            'max_views.max' => 'Le nombre de lectures ne peut pas dépasser 100.',
            'creator_email.email' => 'L\'adresse email n\'est pas valide.',
        ];
    }
}
