<?php

namespace App\Http\Requests\Api\V1\CombatLog\EnemyFailure;

use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\Mapping\MappingVersion;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CombatLogEnemyFailureIndexRequest extends APIFormRequest
{
    public const int LIMIT_DEFAULT = 1000;

    public const int LIMIT_MAX = 1000;

    protected function getRequestDtoClass(): ?string
    {
        return null;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'after_id'           => ['nullable', 'integer', 'min:0'],
            'limit'              => ['nullable', 'integer', 'min:1', sprintf('max:%d', self::LIMIT_MAX)],
            'mapping_version_id' => ['nullable', 'integer', Rule::exists(MappingVersion::class, 'id')],
            'npc_id'             => ['nullable', 'array'],
            'npc_id.*'           => ['integer'],
            'since'              => ['nullable', 'date'],
        ];
    }

    public function getAfterId(): int
    {
        return (int)($this->validated('after_id') ?? 0);
    }

    public function getLimit(): int
    {
        return (int)($this->validated('limit') ?? self::LIMIT_DEFAULT);
    }

    public function getMappingVersionId(): ?int
    {
        $mappingVersionId = $this->validated('mapping_version_id');

        return $mappingVersionId === null ? null : (int)$mappingVersionId;
    }

    /**
     * @return int[]|null
     */
    public function getNpcIds(): ?array
    {
        $npcIds = $this->validated('npc_id');

        return empty($npcIds) ? null : array_map(intval(...), $npcIds);
    }

    public function getSince(): ?Carbon
    {
        $since = $this->validated('since');

        return $since === null ? null : Carbon::parse($since);
    }
}
