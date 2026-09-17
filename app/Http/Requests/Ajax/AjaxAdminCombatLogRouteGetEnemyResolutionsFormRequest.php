<?php

namespace App\Http\Requests\Ajax;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxAdminCombatLogRouteGetEnemyResolutionsFormRequest extends FormRequest
{
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

    public function metric(): EnemyResolutionHeatmapMetric
    {
        return EnemyResolutionHeatmapMetric::tryFrom((string)$this->validated('metric')) ?? EnemyResolutionHeatmapMetric::Average;
    }

    public function minDistance(): ?float
    {
        $minDistance = $this->validated('min_distance');

        return $minDistance === null || $minDistance === '' ? null : (float)$minDistance;
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
            'metric'             => ['nullable', Rule::enum(EnemyResolutionHeatmapMetric::class)],
            'min_distance'       => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
