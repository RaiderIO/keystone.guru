<?php

namespace App\Http\Requests\WalkGraph;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class AjaxWalkGraphAreaFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Exists>>
     */
    public function rules(): array
    {
        return [
            'mapping_version_id' => [
                'nullable',
                'integer',
                Rule::exists(MappingVersion::class, 'id')->where('dungeon_id', $this->getFloor()->dungeon_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mapping_version_id.exists' => __('validation.custom.walkgraph_mapping_version_id.exists'),
        ];
    }

    /**
     * The mapping version whose floor unions place the floors on a facade: the requested one, or else the dungeon's
     * current one.
     */
    public function getMappingVersion(): ?MappingVersion
    {
        return once(function (): ?MappingVersion {
            $mappingVersionId = $this->validated('mapping_version_id');
            if ($mappingVersionId === null) {
                return $this->getFloor()->dungeon->getCurrentMappingVersion();
            }

            return MappingVersion::query()
                ->where('dungeon_id', $this->getFloor()->dungeon_id)
                ->findOrFail($mappingVersionId);
        });
    }

    public function getFloor(): Floor
    {
        /** @var Floor $floor */
        $floor = $this->route('floor');

        return $floor;
    }
}
