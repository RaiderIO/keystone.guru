<?php

namespace App\Http\Requests\EnemyPatrol;

use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPatrolFormRequest extends FormRequest
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
            'id'                      => 'int',
            'mapping_version_id'      => ['required', Rule::exists(MappingVersion::class, 'id')],
            'floor_id'                => ['required', Rule::exists(Floor::class, 'id')],
            'polyline_id'             => ['nullable', Rule::exists(Polyline::class, 'id')],
            'teeming'                 => [Rule::in(array_merge(Enemy::TEEMING_ALL, ['', null]))],
            'faction'                 => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
            'polyline.color'          => 'string',
            'polyline.color_animated' => 'nullable|string',
            'polyline.weight'         => 'int',
            'polyline.vertices_json'  => 'json',
        ];
    }
}
