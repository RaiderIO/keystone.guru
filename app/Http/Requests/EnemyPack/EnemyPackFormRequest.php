<?php

namespace App\Http\Requests\EnemyPack;

use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPackFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'                 => 'int',
            'mapping_version_id' => ['required', Rule::exists(MappingVersion::class, 'id')],
            'floor_id'           => ['required', Rule::exists(Floor::class, 'id')],
            'group'              => 'nullable|int',
            'color'              => 'nullable|string',
            'color_animated'     => 'nullable|string',
            'teeming'            => [Rule::in(array_merge(Enemy::TEEMING_ALL, ['', null]))],
            'faction'            => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
            'label'              => 'string',
            'vertices'           => 'array',
        ];
    }
}
