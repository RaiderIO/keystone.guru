<?php

namespace App\Http\Requests\Npc;

use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcEnemyForcesFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'mapping_version_id'   => Rule::exists(MappingVersion::class, 'id'),
            'enemy_forces'         => 'required|int',
            'enemy_forces_teeming' => 'nullable|int',
        ];
    }
}
