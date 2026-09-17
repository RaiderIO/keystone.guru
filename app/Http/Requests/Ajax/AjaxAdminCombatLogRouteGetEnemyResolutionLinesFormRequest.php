<?php

namespace App\Http\Requests\Ajax;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest extends FormRequest
{
    /** Drawing every recorded match would bury the map in lines; the worst of them are the ones worth looking at. */
    public const int LIMIT_DEFAULT = 250;

    public const int LIMIT_MAX = 1000;

    public function authorize(): bool
    {
        return true;
    }

    public function dungeon(): Dungeon
    {
        return once(fn() => Dungeon::findOrFail($this->validated('dungeon_id')));
    }

    public function mappingVersion(): MappingVersion
    {
        return once(fn() => MappingVersion::findOrFail($this->validated('mapping_version_id')));
    }

    public function minDistance(): ?float
    {
        $minDistance = $this->validated('min_distance');

        return $minDistance === null || $minDistance === '' ? null : (float)$minDistance;
    }

    public function limit(): int
    {
        return (int)($this->validated('limit') ?? self::LIMIT_DEFAULT);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'dungeon_id'         => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'mapping_version_id' => ['required', 'integer', Rule::exists(MappingVersion::class, 'id')],
            'npc_id'             => ['nullable', 'array'],
            'npc_id.*'           => ['integer'],
            'min_distance'       => ['nullable', 'numeric', 'min:0'],
            'limit'              => ['nullable', 'integer', 'min:1', sprintf('max:%d', self::LIMIT_MAX)],
        ];
    }
}
