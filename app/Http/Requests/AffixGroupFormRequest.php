<?php

namespace App\Http\Requests;

use App\Models\Affix;
use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffixGroupFormRequest extends FormRequest
{
    public const int SLOT_COUNT = 6;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        $affixIds             = Affix::all('id')->pluck('id')->toArray();
        $affixIdsWithNullable = array_merge($affixIds, [-1]);

        $rules = [
            'seasonal_index' => 'nullable|integer|min:0',
            'confirmed'      => 'nullable|boolean',
            'affix_id_1'     => ['required', Rule::in($affixIds)],
            'key_level_1'    => 'required|integer|min:1',
        ];

        for ($slot = 2; $slot <= self::SLOT_COUNT; $slot++) {
            $rules[sprintf('affix_id_%d', $slot)]  = ['nullable', Rule::in($affixIdsWithNullable)];
            $rules[sprintf('key_level_%d', $slot)] = [
                Rule::requiredIf(fn() => (int)$this->input(sprintf('affix_id_%d', $slot), -1) !== -1),
                'nullable',
                'integer',
                'min:1',
            ];
        }

        return $rules;
    }
}
