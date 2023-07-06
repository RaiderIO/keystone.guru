<?php

namespace App\Http\Requests\MapIcon;

use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapIconFormRequest extends FormRequest
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
            'id'                         => 'int',
            'mapping_version_id'         => ['nullable', Rule::exists(MappingVersion::class, 'id')],
            'floor_id'                   => ['required', Rule::exists(Floor::class, 'id')],
            'dungeon_route_id'           => ['nullable', Rule::exists(DungeonRoute::class, 'id')],
            'team_id'                    => ['nullable', Rule::exists(Team::class, 'id')],
            'map_icon_type_id'           => ['nullable', Rule::exists(MapIconType::class, 'id')],
            'linked_awakened_obelisk_id' => 'nullable|int',
            'lat'                        => 'numeric',
            'lng'                        => 'numeric',
            'comment'                    => 'nullable|string',
            'permanent_tooltip'          => 'boolean',
            'seasonal_index'             => 'nullable|int',
        ];
    }
}
