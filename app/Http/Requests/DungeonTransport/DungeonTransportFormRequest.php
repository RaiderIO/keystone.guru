<?php

namespace App\Http\Requests\DungeonTransport;

use App\Models\Dungeon;
use App\Models\DungeonTransport;
use App\Models\Floor\Floor;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonTransportFormRequest extends FormRequest
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
            'linked_dungeon_transport_id' => (int)$this->linked_dungeon_transport_id === -1 ? null : $this->linked_dungeon_transport_id,
            'target_dungeon_id'           => (int)$this->target_dungeon_id === -1 ? null : $this->target_dungeon_id,
            'link_key'                    => ($this->link_key ?? '') === '' ? null : $this->link_key,
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
            'map_icon_type_id' => [
                'required',
                'integer',
                Rule::exists(MapIconType::class, 'id'),
            ],
            'linked_dungeon_transport_id' => [
                'nullable',
                'integer',
                Rule::exists(DungeonTransport::class, 'id')
                    ->where('mapping_version_id', $this->getRouteMappingVersion()->id),
                Rule::notIn([(int)$this->input('id')]),
            ],
            'target_dungeon_id' => [
                'nullable',
                'integer',
                Rule::exists(Dungeon::class, 'id'),
            ],
            'link_key' => [
                'nullable',
                'string',
                'max:255',
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'linked_dungeon_transport_id.exists' => __('validation.custom.dungeon_transport_linked_dungeon_transport_id.exists'),
            'linked_dungeon_transport_id.not_in' => __('validation.custom.dungeon_transport_linked_dungeon_transport_id.not_in'),
        ];
    }

    /**
     * The mapping version the transport is stored in; the controller ignores the one in the request body.
     */
    private function getRouteMappingVersion(): MappingVersion
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $this->route('mappingVersion');

        return $mappingVersion;
    }
}
