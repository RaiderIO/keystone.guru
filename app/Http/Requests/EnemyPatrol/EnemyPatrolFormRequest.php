<?php

namespace App\Http\Requests\EnemyPatrol;

use App\Http\Requests\Traits\CastInputData;
use App\Http\Requests\Traits\ValidatesMappingPolyline;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPatrolFormRequest extends FormRequest
{
    use CastInputData;
    use ValidatesMappingPolyline;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->castInputData($this, EnemyPatrol::class);
        $this->castInputData($this, Polyline::class, 'polyline');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'id'                 => 'int',
            'mapping_version_id' => [
                'required',
                'int',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                'int',
                Rule::exists(Floor::class, 'id'),
            ],
            'polyline_id' => [
                'nullable',
                Rule::exists(Polyline::class, 'id'),
            ],
            'teeming' => [
                Rule::in(array_merge(Enemy::TEEMING_ALL, [
                    '',
                    null,
                ])),
            ],
            'faction' => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
        ], $this->mappingPolylineRules());
    }
}
