<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Tags\TagCategory;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * What the form for a new collection starts from: a game version and season, and optionally one of the user's own
 * routes ("New collection with this route") or one of their personal tags ("start from tag") whose routes are
 * pre-filled. Every parameter is optional.
 */
class DungeonRouteCollectionCreateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id ?? 0;

        return [
            'game_version_id' => [
                'nullable',
                'integer',
                Rule::exists('game_versions', 'id')->where('active', 1),
            ],
            // Posted empty for a free-form collection. Whether the season fits the game version is checked in after()
            'season_id' => [
                'nullable',
                'integer',
                Rule::exists('seasons', 'id'),
            ],
            'dungeon_route' => [
                'nullable',
                'string',
                Rule::exists('dungeon_routes', 'public_key')
                    ->where('author_id', $userId)
                    ->whereNull('expires_at'),
            ],
            'tag' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('tags', 'name')
                    ->where('context_id', $userId)
                    ->where('context_class', User::class)
                    ->where('tag_category_id', TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL]),
            ],
            'name' => [
                'nullable',
                'string',
                'max:128',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'game_version_id.exists' => __('validation.custom.collection_game_version_id.exists'),
            'season_id.exists'       => __('validation.custom.collection_season_id.exists'),
            'dungeon_route.exists'   => __('validation.custom.collection_dungeon_routes.exists'),
            'tag.exists'             => __('validation.custom.collection_tag.exists'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['game_version_id', 'season_id', 'dungeon_route']) || !$this->filled('season_id')) {
                    return;
                }

                $gameVersion = $this->gameVersion();
                $season      = $this->season();
                if ($gameVersion === null || !$gameVersion->has_seasons) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.no_seasons'));
                } elseif ($season?->expansion_id !== $gameVersion->expansion_id) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.expansion'));
                }
            },
        ];
    }

    public function dungeonRoute(): ?DungeonRoute
    {
        return once(function (): ?DungeonRoute {
            $publicKey = $this->input('dungeon_route');

            return $publicKey === null ? null : DungeonRoute::query()
                ->with(['mappingVersion.gameVersion', 'season'])
                ->where('public_key', $publicKey)
                ->firstOrFail();
        });
    }

    /**
     * The posted game version, else the game version of the passed route when that one is active, else null.
     */
    public function gameVersion(): ?GameVersion
    {
        return once(function (): ?GameVersion {
            $gameVersionId = $this->input('game_version_id');
            if ($gameVersionId !== null) {
                return GameVersion::query()->findOrFail((int)$gameVersionId);
            }

            $gameVersion = $this->dungeonRoute()?->mappingVersion?->gameVersion;

            return $gameVersion !== null && $gameVersion->active ? $gameVersion : null;
        });
    }

    /**
     * Whether the request decides the season: a posted season (empty for free-form), or a passed route.
     */
    public function hasSeason(): bool
    {
        return $this->has('season_id') || $this->dungeonRoute() !== null;
    }

    /**
     * The posted season, else the passed route's own season when a set of it may exist on the game version, else null
     * (free-form).
     */
    public function season(): ?Season
    {
        return once(function (): ?Season {
            if ($this->has('season_id')) {
                $seasonId = $this->input('season_id');

                return $seasonId === null ? null : Season::query()->findOrFail((int)$seasonId);
            }

            $season      = $this->dungeonRoute()?->season;
            $gameVersion = $this->gameVersion();
            if ($season === null || $gameVersion === null || !$gameVersion->has_seasons || $season->expansion_id !== $gameVersion->expansion_id) {
                return null;
            }

            return $season;
        });
    }

    public function tagName(): ?string
    {
        $tag = $this->validated('tag');

        return is_string($tag) ? $tag : null;
    }
}
