<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Models\Mapping\MappingVersion;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminToolsCombatLogRouteEnemyFailuresRequest extends FormRequest
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
        ];
    }

    /**
     * The mapping version the failures should be limited to, or null when the dungeon's current one should be used.
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
