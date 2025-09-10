<?php

namespace App\Http\Requests;

use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        //return Auth::user()?->hasRole(Role::ROLE_ALL) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route()->parameter('user');

        return [
            'avatar' => [
                'nullable',
                File::image()
                    ->min(1)
                    ->max(250)
                    ->dimensions(Rule::dimensions()->maxWidth(256)->maxHeight(256))
                    ->extensions([
                        'jpg',
                        'jpeg',
                        'png',
                    ]),
            ],
            'name' => [
                'nullable',
                'alpha_dash',
                'min:3',
                'max:24',
                Rule::unique('users', 'id')->ignore($user, 'id'),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user, 'id'),
            ],
            'game_server_region_id' => [
                'nullable',
                Rule::in(array_merge([0], array_values(GameServerRegion::ALL))),
            ],
            'echo_anonymous' => [
                'nullable',
                'boolean',
            ],
            'echo_color' => [
                'required',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'timezone' => [
                'required',
                'string',
                'timezone',
            ],
        ];
    }
}
