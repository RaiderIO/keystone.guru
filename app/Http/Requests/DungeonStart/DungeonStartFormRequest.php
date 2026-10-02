<?php

namespace App\Http\Requests\DungeonStart;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonStartFormRequest extends FormRequest
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
            'target_dungeon_id' => (int)$this->target_dungeon_id === -1 ? null : $this->target_dungeon_id,
        ]);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
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
            'target_dungeon_id' => [
                'nullable',
                'integer',
                Rule::exists(Dungeon::class, 'id'),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'lat' => 'numeric',
            'lng' => 'numeric',
        ];
    }
}
