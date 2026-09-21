<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Tags\TagCategory;
use App\Models\User;
use App\Service\GameVersion\GameVersionServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The query of the new-collection form: which season it opens with, so its route picker can be rebuilt for whatever
 * season the user picks, and optionally one of the user's own routes ("New collection with this route") or one of
 * their personal tags ("start from tag") whose routes are pre-filled. The game version is always the one selected
 * on the site.
 */
class DungeonRouteCollectionCreateFormRequest extends FormRequest
{
    /**
     * The season_id value that selects a free-form collection.
     */
    public const string SEASON_NONE = 'none';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()->id ?? 0;

        return [
            'season_id' => [
                'nullable',
                Rule::when(
                    static fn(Fluent $input): bool => $input->get('season_id') !== self::SEASON_NONE,
                    ['integer', Rule::exists('seasons', 'id')],
                ),
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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'season_id.exists'     => __('validation.custom.collection_season_id.exists'),
            'dungeon_route.exists' => __('validation.custom.collection_dungeon_routes.exists'),
            'tag.exists'           => __('validation.custom.collection_tag.exists'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['season_id', 'dungeon_route'])) {
                    return;
                }

                $season = $this->season();
                if ($season === null) {
                    return;
                }

                $gameVersion = $this->gameVersion();
                if (!$gameVersion->has_seasons) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.no_seasons'));
                } elseif ($season->expansion_id !== $gameVersion->expansion_id) {
                    $validator->errors()->add('season_id', __('validation.custom.collection_season_id.expansion'));
                }
            },
        ];
    }

    /**
     * The route the new collection starts from, when one was passed.
     */
    public function dungeonRoute(): ?DungeonRoute
    {
        return once(function (): ?DungeonRoute {
            $publicKey = $this->input('dungeon_route');

            return $publicKey === null ? null : DungeonRoute::query()
                ->with(['dungeon', 'mappingVersion.gameVersion', 'season'])
                ->where('public_key', $publicKey)
                ->firstOrFail();
        });
    }

    /**
     * The game version selected on the site, which every new collection is for.
     */
    public function gameVersion(): GameVersion
    {
        return once(function (): GameVersion {
            /** @var GameVersionServiceInterface $gameVersionService */
            $gameVersionService = app(GameVersionServiceInterface::class);

            return $gameVersionService->getGameVersion(Auth::user());
        });
    }

    /**
     * Whether the query names the season, either a season or explicitly free-form.
     */
    public function hasRequestedSeason(): bool
    {
        return $this->query('season_id') !== null;
    }

    /**
     * The requested season, else the passed route's own season when a set of it may exist on the game version, else
     * null (free-form).
     */
    public function season(): ?Season
    {
        return once(function (): ?Season {
            $seasonId = $this->query('season_id');

            if ($seasonId !== null) {
                return $seasonId === self::SEASON_NONE ? null : Season::query()->findOrFail((int)$seasonId);
            }

            $season      = $this->dungeonRoute()?->season;
            $gameVersion = $this->gameVersion();
            if ($season === null || !$gameVersion->has_seasons || $season->expansion_id !== $gameVersion->expansion_id) {
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
