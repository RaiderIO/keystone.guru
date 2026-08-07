<?php

namespace App\Http\Requests;

use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileFormRequest extends FormRequest
{
    /**
     * Deliberately authorized here and not only in ProfileController::update(): authorize() runs
     * BEFORE validation, whereas a controller-side gate runs after it. rules() below applies
     * `Rule::unique('users', 'email')->ignore($user)` against the route-bound user, so gating only
     * in the controller would let someone probing another account tell "this address is already
     * taken by the account I am probing" (403) from "taken by a different account" (redirect with
     * errors) - an email-to-account oracle. Failing here keeps an unauthorized request away from
     * the validator entirely.
     *
     * ProfileController::update() still calls Gate::authorize('update', $user) as well, so the
     * write is gated even if this request class is ever swapped out, and so it matches its sibling
     * profile.updateprivacy route, which takes a plain Request and can only be gated there.
     */
    public function authorize(): bool
    {
        $user = $this->route()->parameter('user');

        return $user instanceof User && Gate::allows('update', $user);
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
