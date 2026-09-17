<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminToolsCombatLogRouteEnemyResolutionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'dungeon_id'         => ['nullable', 'integer', Rule::exists(Dungeon::class, 'id')],
            'mapping_version_id' => ['nullable', 'integer', Rule::exists(MappingVersion::class, 'id')],
            // The page itself does nothing with these three - they belong to the sidebar. They are validated so that
            // validated() carries them through the redirect to the default floor, which is what lets a shared link to
            // a hot spot come back with the same filters instead of the defaults.
            'metric'       => ['nullable', Rule::enum(EnemyResolutionHeatmapMetric::class)],
            'min_distance' => ['nullable', 'numeric', 'min:0'],
            // Left untyped on purpose: the sidebar serialises its npc selection into the URL comma joined
            // (npc_id=123,456), so anything shape specific here rejects a link the page itself produced
            'npc_id' => ['nullable'],
        ];
    }

    /**
     * The mapping version the resolutions should be limited to, or null when the dungeon's current one should be used.
     */
    public function getMappingVersion(): ?MappingVersion
    {
        return once(function (): ?MappingVersion {
            $mappingVersionId = $this->validated('mapping_version_id');

            if ($mappingVersionId === null || $mappingVersionId === '') {
                return null;
            }

            return MappingVersion::query()->findOrFail((int)$mappingVersionId);
        });
    }
}
