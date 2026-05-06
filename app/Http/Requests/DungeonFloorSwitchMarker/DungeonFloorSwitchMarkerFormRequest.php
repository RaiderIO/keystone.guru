<?php

namespace App\Http\Requests\DungeonFloorSwitchMarker;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @mixin DungeonFloorSwitchMarker
 */
class DungeonFloorSwitchMarkerFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_floor_id'                       => (int)$this->source_floor_id === -1 ? null : $this->source_floor_id,
            'linked_dungeon_floor_switch_marker_id' => (int)$this->linked_dungeon_floor_switch_marker_id === -1 ? null : $this->linked_dungeon_floor_switch_marker_id,
            'direction'                             => (int)$this->direction === -1 ? null : $this->direction,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'                 => 'int',
            'mapping_version_id' => [
                'required',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'source_floor_id' => [
                'nullable',
                Rule::in(array_merge([-1], Floor::all('id')->pluck('id')->toArray())),
            ],
            'target_floor_id' => [
                'nullable',
                Rule::exists(Floor::class, 'id'),
            ],
            'linked_dungeon_floor_switch_marker_id' => [
                'nullable',
                Rule::exists(DungeonFloorSwitchMarker::class, 'id'),
            ],
            'direction' => [
                'nullable',
                Rule::in(array_merge(FloorCoupling::ALL, [
                    '-1',
                    '',
                    null,
                ])),
            ],
            'hidden_in_facade' => [
                'nullable',
                'boolean',
            ],
            'lat' => 'numeric',
            'lng' => 'numeric',
        ];
    }
}
