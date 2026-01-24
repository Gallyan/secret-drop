<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Handle cipher_meta as JSON string from FormData
        if ($this->has('cipher_meta') && is_string($this->cipher_meta)) {
            $this->merge([
                'cipher_meta' => json_decode($this->cipher_meta, true),
            ]);
        }

        // Convert string booleans from FormData
        if ($this->has('usage_unique') && is_string($this->usage_unique)) {
            $this->merge([
                'usage_unique' => in_array($this->usage_unique, ['1', 'true', 'on'], true),
            ]);
        }

        if ($this->has('split_mode') && is_string($this->split_mode)) {
            $this->merge([
                'split_mode' => in_array($this->split_mode, ['1', 'true', 'on'], true),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:text,file'],

            // Text secrets
            'ciphertext' => ['required_if:type,text', 'string'],

            // File secrets
            'encrypted_file' => ['required_if:type,file', 'file', 'max:102400'], // 100MB
            'filename' => ['required_if:type,file', 'string', 'max:255'],
            'mime' => ['required_if:type,file', 'string', 'max:255'],
            'size' => ['required_if:type,file', 'integer', 'min:1', 'max:104857600'], // 100MB

            // Common
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
            'split_mode' => ['boolean'],
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
            'encrypted_file.required_if' => 'Le fichier chiffré est requis.',
            'encrypted_file.max' => 'Le fichier ne doit pas dépasser 100 Mo.',
            'filename.required_if' => 'Le nom du fichier est requis.',
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
