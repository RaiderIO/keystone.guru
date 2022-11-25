<?php

namespace App\Http\Requests\DungeonFloorSwitchMarker;

use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonFloorSwitchMarkerFormRequest extends FormRequest
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
            'target_floor_id'    => ['nullable', Rule::exists(Floor::class, 'id')],
            'lat'                => 'numeric',
            'lng'                => 'numeric',
        ];
    }
}
