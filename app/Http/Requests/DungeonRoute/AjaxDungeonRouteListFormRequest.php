<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * The route table's server-side paging request. Only the scope constraints are validated here; the
 * DataTables parameters (columns, order, start, length, requirements, tags, ...) are read leniently
 * by the controller and the column handlers because the tables send placeholder values for filters
 * they do not render.
 */
class AjaxDungeonRouteListFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function gameVersion(): ?GameVersion
    {
        return once(function (): ?GameVersion {
            $gameVersionId = $this->validated('game_version_id');

            return $gameVersionId === null ? null : GameVersion::findOrFail($gameVersionId);
        });
    }

    public function season(): ?Season
    {
        return once(function (): ?Season {
            $seasonId = $this->validated('season_id');

            return $seasonId === null ? null : Season::findOrFail($seasonId);
        });
    }

    /**
     * @return Collection<int, Dungeon>|null Null when the request does not limit the dungeons.
     */
    public function dungeons(): ?Collection
    {
        return once(function (): ?Collection {
            $dungeonIds = $this->validated('dungeon_ids');

            return $dungeonIds === null ? null : Dungeon::query()->whereIn('id', $dungeonIds)->get();
        });
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'game_version_id' => ['nullable', 'integer', Rule::exists(GameVersion::class, 'id')],
            'season_id'       => ['nullable', 'integer', Rule::exists(Season::class, 'id')],
            'dungeon_ids'     => ['nullable', 'array', 'min:1'],
            'dungeon_ids.*'   => ['integer', 'distinct', Rule::exists(Dungeon::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'game_version_id.exists' => __('validation.custom.route_list_game_version_id.exists'),
            'season_id.exists'       => __('validation.custom.route_list_season_id.exists'),
            'dungeon_ids.min'        => __('validation.custom.route_list_dungeon_ids.min'),
            'dungeon_ids.*.exists'   => __('validation.custom.route_list_dungeon_ids.exists'),
        ];
    }
}
