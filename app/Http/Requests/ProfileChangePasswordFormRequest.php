<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
                'current_password',
            ],
            'new_password' => [
                'required',
                'string',
                Password::defaults(),
                'different:current_password',
            ],
            'new_password-confirm' => [
                'required',
                'string',
                'same:new_password',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.required'         => __('validation.custom.change_password.current_password_required'),
            'new_password.required'             => __('validation.custom.change_password.new_password_required'),
            'new_password-confirm.required'     => __('validation.custom.change_password.new_password_confirm_required'),
            'current_password.current_password' => __('controller.profile.flash.current_password_is_incorrect'),
            'new_password.different'            => __('controller.profile.flash.new_password_equals_old_password'),
            'new_password-confirm.same'         => __('controller.profile.flash.new_passwords_do_not_match'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return sprintf('%s#change-password', route('profile.edit'));
    }
}
