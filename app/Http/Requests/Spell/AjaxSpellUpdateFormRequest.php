<?php

namespace App\Http\Requests\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownGroup;
use App\Models\Spell\SpellDispelType;
use App\Models\Spell\SpellSchool;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxSpellUpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ADMIN) ?? false;
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'name'            => 'string',
            'game_version_id' => [
                'nullable',
                Rule::exists(GameVersion::class, 'id'),
            ],
            'icon_name'      => 'string',
            'category'       => Rule::in(SpellCategory::values()),
            'dispel_type'    => Rule::in(SpellDispelType::translationKeys()),
            'cooldown_group' => Rule::in(SpellCooldownGroup::values()),
            'schools'        => 'array',
            'schools.*'      => Rule::in(array_keys(SpellSchool::slugsByBit())),
            'aura'           => 'boolean',
            'selectable'     => 'boolean',
            'hidden_on_map'  => 'boolean',
        ];
    }
}
