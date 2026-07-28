<?php

namespace App\Http\Requests\EnemyForcesCheckpoint;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyForcesCheckpointFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
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
            // A checkpoint is placed on the map first and named afterwards, so the very first save
            // legitimately carries no name yet.
            'name' => 'nullable|string|max:255',
            'lat'  => 'required|numeric',
            'lng'  => 'required|numeric',
        ];
    }
}
