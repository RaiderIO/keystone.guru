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
     * Authorization is done in ProfileController::update() via Gate::authorize('update', $user),
     * matching how the rest of the application gates its write endpoints. It has to live there
     * rather than here because the sibling profile.updateprivacy route takes a plain Request and
     * needs the same check.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
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
                Rule::unique('users', 'name')->ignore($user, 'id'),
            ],
            'email' => [
                'nullable',
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
