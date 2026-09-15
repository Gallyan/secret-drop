<?php

namespace App\Http\Requests;

use App\Enums\SecretType;
use App\Rules\Base64UrlBytes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            $decoded = json_decode($this->cipher_meta, true);
            $this->merge([
                'cipher_meta' => is_array($decoded) ? $decoded : [],
            ]);
        }

        // Convert string booleans from FormData
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
            'type' => ['required', Rule::enum(SecretType::class)],

            // Text secrets (~50 KB plaintext = ~70 KB ciphertext in base64)
            'ciphertext' => [
                'bail', 'required_if:type,text', 'string', 'max:70000',
                new Base64UrlBytes(minBytes: 16),
            ],

            // File secrets: 10 MB before encryption = ~14 MB after (metadata encrypted in payload)
            'encrypted_file' => ['required_if:type,file', 'file', 'max:14336'], // ~14 MB

            // Common
            'cipher_meta' => ['required', 'array'],
            'cipher_meta.alg' => ['required', 'in:AES-256-GCM'],
            'cipher_meta.iv' => ['bail', 'required', 'string', new Base64UrlBytes(exactBytes: 12)],
            'cipher_meta.version' => ['required', 'integer', 'min:1'],
            'cipher_meta.salt' => ['bail', 'nullable', 'string', new Base64UrlBytes(exactBytes: 16)],
            'cipher_meta.iv2' => ['bail', 'nullable', 'string', new Base64UrlBytes(exactBytes: 12)],
            'cipher_meta.kdf' => ['nullable', 'string'],
            'cipher_meta.has_passphrase' => ['boolean'],
            'expiration' => ['required', 'in:'.implode(',', array_keys(Config::array('secrets.expirations')))],
            'max_views' => ['nullable', 'integer', 'min:1', 'max:100'],
            'creator_email' => ['nullable', 'email', 'max:255'],
            'split_mode' => ['boolean'],
        ];
    }

    /**
     * Typed readers over the validated payload.
     *
     * They all go through validated(), never input(): the rules above stay the
     * only gate, and callers get a guaranteed type instead of mixed.
     */
    public function secretType(): SecretType
    {
        $type = $this->validated('type');

        return SecretType::from(is_string($type) ? $type : '');
    }

    public function expiration(): string
    {
        $expiration = $this->validated('expiration');

        return is_string($expiration) ? $expiration : '';
    }

    public function ciphertext(): string
    {
        $ciphertext = $this->validated('ciphertext');

        return is_string($ciphertext) ? $ciphertext : '';
    }

    /** @return array<string, mixed> */
    public function cipherMeta(): array
    {
        $meta = $this->validated('cipher_meta');

        return is_array($meta) ? $meta : [];
    }

    public function creatorEmail(): ?string
    {
        $email = $this->validated('creator_email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function maxViews(): ?int
    {
        $maxViews = $this->validated('max_views');

        return is_numeric($maxViews) ? (int) $maxViews : null;
    }

    public function hasPassphrase(): bool
    {
        return (bool) $this->validated('cipher_meta.has_passphrase');
    }

    public function isSplitMode(): bool
    {
        return (bool) $this->validated('split_mode');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $meta = $this->input('cipher_meta', []);

            if (! is_array($meta)) {
                return;
            }

            $hasSalt = ! empty($meta['salt']);
            $hasIv2 = ! empty($meta['iv2']);
            $hasPassphrase = ! empty($meta['has_passphrase']);

            if ($hasSalt !== $hasIv2) {
                $validator->errors()->add(
                    'cipher_meta.salt',
                    __('messages.val_salt_iv2_consistency')
                );
            }

            if ($hasPassphrase && ! $hasSalt) {
                $validator->errors()->add(
                    'cipher_meta.has_passphrase',
                    __('messages.val_passphrase_requires_salt')
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => __('messages.val_type_required'),
            'type.enum' => __('messages.val_type_in'),
            'ciphertext.required_if' => __('messages.val_ciphertext_required'),
            'encrypted_file.required_if' => __('messages.val_file_required'),
            'ciphertext.max' => __('messages.val_ciphertext_max'),
            'encrypted_file.max' => __('messages.val_file_max'),
            'cipher_meta.required' => __('messages.val_cipher_meta_required'),
            'cipher_meta.alg.required' => __('messages.val_cipher_alg_required'),
            'cipher_meta.iv.required' => __('messages.val_cipher_iv_required'),
            'cipher_meta.version.required' => __('messages.val_cipher_version_required'),
            'expiration.required' => __('messages.val_expiration_required'),
            'expiration.in' => __('messages.val_expiration_in'),
            'max_views.min' => __('messages.val_max_views_min'),
            'max_views.max' => __('messages.val_max_views_max'),
            'creator_email.email' => __('messages.val_email_invalid'),
            'creator_email.max' => __('messages.val_email_max'),
        ];
    }
}
