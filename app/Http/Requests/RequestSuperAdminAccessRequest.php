<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestSuperAdminAccessRequest extends FormRequest
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
            'email' => ['required', 'email'],
        ];
    }

    /** Reads the validated value, so the rules above stay the single source of truth. */
    public function email(): string
    {
        $email = $this->validated('email');

        return is_string($email) ? $email : '';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('messages.val_email_required'),
            'email.email' => __('messages.val_email_invalid'),
        ];
    }
}
