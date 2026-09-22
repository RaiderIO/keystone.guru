<?php

namespace App\Http\Requests\MountableArea;

use App\Http\Requests\Traits\ValidatesMappingPolyline;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MountableAreaFormRequest extends FormRequest
{
    use ValidatesMappingPolyline;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return array_merge([
            'id'                 => 'required:int',
            'mapping_version_id' => [
                'required',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'speed' => 'nullable|int',
        ], $this->mappingPolylineRules());
    }
}
