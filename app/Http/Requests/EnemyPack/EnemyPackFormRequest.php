<?php

namespace App\Http\Requests\EnemyPack;

use App\Http\Requests\Traits\ValidatesMappingPolyline;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPackFormRequest extends FormRequest
{
    use ValidatesMappingPolyline;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'id'                 => 'int',
            'mapping_version_id' => [
                'required',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'group'   => 'nullable|int',
            'teeming' => [
                Rule::in(array_merge(Enemy::TEEMING_ALL, [
                    '',
                    null,
                ])),
            ],
            'faction' => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
            'label'   => 'string',
        ], $this->mappingPolylineRules());
    }
}
