<?php

namespace App\Http\Requests;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Team;
use App\Models\User;
use App\Rules\VanityKeyRule;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class TeamFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('vanity_key')) {
            $vanityKey = strtolower(trim((string)$this->get('vanity_key')));

            $this->merge(['vanity_key' => $vanityKey === '' ? null : $vanityKey]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Team|null $team */
        $team = $this->route()->parameter('team');

        if ($team === null) {
            $nameRules = 'required|string|max:32|unique:teams';
        } else {
            $nameRules = Rule::unique('teams')->ignore($team);
        }

        /** @var User|null $user */
        $user            = Auth::user();
        $canSetVanityKey = $user !== null && $user->hasPatreonBenefit(PatreonBenefit::CUSTOM_URLS);

        return [
            'name'        => $nameRules,
            'description' => 'string|nullable',
            'vanity_key'  => $canSetVanityKey
                ? ['nullable', 'string', new VanityKeyRule(Team::class, $team, Team::RESERVED_VANITY_KEYS)]
                : ['prohibited'],
            'logo' => [
                'nullable',
                File::image()
                    ->min(1)
                    ->max(500)
                    ->dimensions(Rule::dimensions()->maxWidth(512)->maxHeight(512))
                    ->extensions([
                        'jpg',
                        'jpeg',
                        'png',
                    ]),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vanity_key.prohibited' => __('validation.custom.vanity_key.prohibited'),
        ];
    }
}
