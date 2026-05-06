<?php

namespace App\Http\Requests\Floor;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FloorUnionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'                 => 'required:int',
            'mapping_version_id' => [
                'required',
                Rule::exists(MappingVersion::class, 'id'),
            ],
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'target_floor_id' => [
                'nullable',
                Rule::exists(Floor::class, 'id'),
            ],
            'lat'      => 'numeric',
            'lng'      => 'numeric',
            'size'     => 'numeric',
            'rotation' => 'numeric',
        ];
    }
}
