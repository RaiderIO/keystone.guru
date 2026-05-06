<?php

namespace App\Http\Requests\MapIcon;

use App\Http\Requests\Traits\CastInputData;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Team;
use App\Rules\MapIconTypeRoleCheckRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapIconFormRequest extends FormRequest
{
    use CastInputData;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->castInputData($this, MapIcon::class));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'                 => 'int',
            'mapping_version_id' => [
                'nullable',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'dungeon_route_id' => [
                'nullable',
                Rule::exists(DungeonRoute::class, 'id'),
            ],
            'team_id' => [
                'nullable',
                Rule::exists(Team::class, 'id'),
            ],
            'map_icon_type_id' => [
                'nullable',
                Rule::exists(MapIconType::class, 'id'),
                new MapIconTypeRoleCheckRule(),
            ],
            'linked_awakened_obelisk_id' => 'nullable|int',
            'lat'                        => 'numeric',
            'lng'                        => 'numeric',
            'comment'                    => 'nullable|string',
            'permanent_tooltip'          => 'boolean',
            'seasonal_index'             => 'nullable|int',
        ];
    }
}
