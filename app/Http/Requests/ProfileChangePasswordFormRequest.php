<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileChangePasswordFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The route sits behind the auth middleware and only ever changes the signed-in user's own password.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
            ],
            'new_password' => [
                'required',
                'string',
            ],
            'new_password-confirm' => [
                'required',
                'string',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.required'     => __('validation.custom.change_password.current_password_required'),
            'new_password.required'         => __('validation.custom.change_password.new_password_required'),
            'new_password-confirm.required' => __('validation.custom.change_password.new_password_confirm_required'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return sprintf('%s#change-password', route('profile.edit'));
    }
}
