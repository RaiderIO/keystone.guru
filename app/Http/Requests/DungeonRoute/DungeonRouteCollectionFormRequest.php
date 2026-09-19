<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class DungeonRouteCollectionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->dungeonRouteCollectionOwnerId();

        return [
            'name' => [
                'required',
                'string',
                'max:128',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'published_state' => [
                'required',
                'string',
                Rule::in(DungeonRouteCollection::AVAILABLE_PUBLISHED_STATES),
            ],
            // A collection may only be shared with a team the user is actually a member of
            'team_id' => [
                'nullable',
                'integer',
                sprintf('required_if:published_state,%s', PublishedState::TEAM),
                Rule::exists('team_users', 'team_id')
                    ->where('user_id', $userId),
            ],
            // Required for a collection without one yet; any other keeps its game version when none is posted
            'game_version_id' => array_merge($this->existingDungeonRouteCollection()?->game_version_id === null ? [] : ['sometimes'], [
                'required',
                'integer',
                $this->gameVersionExistsRule(),
            ]),
            // Null makes a free-form collection. Whether the season fits the game version is checked in after()
            'season_id' => [
                'nullable',
                'integer',
                Rule::exists('seasons', 'id'),
            ],
            // Optional: a collection without a category is perfectly valid
            'category_id' => [
                'nullable',
                'integer',
                'exists:dungeon_route_collection_categories,id',
            ],
            'dungeon_routes' => [
                'nullable',
                'array',
                sprintf('max:%d', DungeonRouteCollection::MAX_ROUTES),
            ],
            // A user may only collect their own routes - without the author_id constraint anyone
            // could put (and thereby surface) someone else's route in their collection
            'dungeon_routes.*' => [
                'required',
                'integer',
                // dungeon_route_collection_routes is unique on (collection, route): the form
                // cannot produce duplicates, but a hand-crafted post could, and the insert would
                // then fail on the constraint rather than as a validation error
                'distinct',
                Rule::exists('dungeon_routes', 'id')
                    ->where('author_id', $userId),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'             => __('validation.custom.collection_name.required'),
            'name.max'                  => __('validation.custom.collection_name.max'),
            'description.max'           => __('validation.custom.collection_description.max'),
            'team_id.required_if'       => __('validation.custom.collection_team_id.required_if'),
            'team_id.exists'            => __('validation.custom.collection_team_id.exists'),
            'category_id.exists'        => __('validation.custom.collection_category_id.exists'),
            'game_version_id.required'  => __('validation.custom.collection_game_version_id.required'),
            'game_version_id.exists'    => __('validation.custom.collection_game_version_id.exists'),
            'season_id.exists'          => __('validation.custom.collection_season_id.exists'),
            'dungeon_routes.max'        => __('validation.custom.collection_dungeon_routes.max'),
            'dungeon_routes.*.exists'   => __('validation.custom.collection_dungeon_routes.exists'),
            'dungeon_routes.*.distinct' => __('validation.custom.collection_dungeon_routes.distinct'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['game_version_id', 'season_id'])) {
                    return;
                }

                $this->validateGameVersionChange($validator);
                $this->validateSeason($validator);
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['game_version_id', 'season_id', 'dungeon_routes', 'dungeon_routes.*'])) {
                    return;
                }

                $this->validateDungeonRoutesMatchTheCollection($validator);
            },
        ];
    }

    /**
     * The game version the collection has after saving: the posted one, or the existing collection's own.
     */
    public function gameVersion(): ?GameVersion
    {
        return once(function (): ?GameVersion {
            $gameVersionId = $this->input('game_version_id');

            if ($gameVersionId === null) {
                return $this->existingDungeonRouteCollection()?->gameVersion;
            }

            return GameVersion::query()->findOrFail((int)$gameVersionId);
        });
    }

    /**
     * The season the collection has after saving: the posted one (null makes it free-form), or the existing
     * collection's own when none is posted.
     */
    public function season(): ?Season
    {
        return once(function (): ?Season {
            if (!$this->has('season_id')) {
                return $this->existingDungeonRouteCollection()?->season;
            }

            $seasonId = $this->input('season_id');

            return $seasonId === null ? null : Season::query()->findOrFail((int)$seasonId);
        });
    }

    /**
     * The published state to persist, as an id.
     */
    public function publishedStateId(): int
    {
        return PublishedState::ALL[$this->validated('published_state')];
    }

    /**
     * The team this collection is shared with, if any. Only relevant for the team published state -
     * a collection that is not team published never keeps a team behind.
     */
    public function team(): ?Team
    {
        return once(function (): ?Team {
            $teamId = $this->validated('team_id');

            if ($teamId === null || $this->validated('published_state') !== PublishedState::TEAM) {
                return null;
            }

            return Team::query()->findOrFail($teamId);
        });
    }

    /**
     * The category this collection is filed under, if any.
     */
    public function dungeonRouteCollectionCategory(): ?DungeonRouteCollectionCategory
    {
        return once(function (): ?DungeonRouteCollectionCategory {
            $categoryId = $this->validated('category_id');

            if ($categoryId === null) {
                return null;
            }

            return DungeonRouteCollectionCategory::query()->findOrFail($categoryId);
        });
    }

    /**
     * The routes to collect, in submitted order, already constrained to the current user's own
     * routes.
     *
     * @return Collection<int, DungeonRoute>
     */
    public function dungeonRoutes(): Collection
    {
        return once(function (): Collection {
            /** @var array<int, int> $ids */
            $ids = $this->validated('dungeon_routes') ?? [];

            if ($ids === []) {
                return collect();
            }

            $dungeonRoutes = DungeonRoute::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            // Preserve the order the user submitted them in, which becomes the display order
            return collect($ids)
                ->map(static fn(int $id): ?DungeonRoute => $dungeonRoutes->get($id))
                ->filter()
                ->values();
        });
    }

    /**
     * A collection's game version is fixed once it holds a route.
     */
    private function validateGameVersionChange(Validator $validator): void
    {
        $dungeonRouteCollection = $this->existingDungeonRouteCollection();
        $gameVersionId          = $this->input('game_version_id');

        // A collection without a game version yet must be able to get one, whatever it holds
        if ($dungeonRouteCollection?->game_version_id === null || $gameVersionId === null || (int)$gameVersionId === $dungeonRouteCollection->game_version_id) {
            return;
        }

        if ($dungeonRouteCollection->dungeonRouteCollectionRoutes()->exists()) {
            $validator->errors()->add('game_version_id', __('validation.custom.collection_game_version_id.fixed'));
        }
    }

    /**
     * A season only exists on a game version with seasons, must be of that game version's expansion, and is fixed
     * at creation: an existing collection's season may only stay the same or be dropped. Checked on creation and
     * whenever the game version or season changes.
     */
    private function validateSeason(Validator $validator): void
    {
        $seasonId               = $this->input('season_id');
        $dungeonRouteCollection = $this->existingDungeonRouteCollection();
        if ($seasonId !== null && $dungeonRouteCollection !== null && (int)$seasonId !== $dungeonRouteCollection->season_id) {
            $validator->errors()->add('season_id', __('validation.custom.collection_season_id.fixed'));

            return;
        }

        // The season kept when none is posted must fit the game version just as much as a posted one
        $season = $this->season();
        if ($season === null) {
            return;
        }

        // A binding that does not change always stands, so a season set stays editable after its expansion moved on
        if ($dungeonRouteCollection !== null
            && $this->gameVersion()?->id === $dungeonRouteCollection->game_version_id
            && $season->id === $dungeonRouteCollection->season_id) {
            return;
        }

        $gameVersion = $this->gameVersion();
        if ($gameVersion === null || !$gameVersion->has_seasons) {
            $validator->errors()->add('season_id', __('validation.custom.collection_season_id.no_seasons'));

            return;
        }

        if ($season->expansion_id !== $gameVersion->expansion_id) {
            $validator->errors()->add('season_id', __('validation.custom.collection_season_id.expansion'));
        }
    }

    /**
     * A route joins a collection only when its own mapping version is of the collection's game version and, for a
     * season set, it is of the collection's season. Routes already in the collection may stay, so the owner of a
     * collection that predates these rules can still save it.
     */
    private function validateDungeonRoutesMatchTheCollection(Validator $validator): void
    {
        /** @var array<int, int|string> $ids */
        $ids = (array)($this->input('dungeon_routes') ?? []);
        if ($ids === []) {
            return;
        }

        $memberIds = $this->existingDungeonRouteCollection()
            ?->dungeonRouteCollectionRoutes()
            ->pluck('dungeon_route_id')
            ->all() ?? [];

        $gameVersion = $this->gameVersion();
        $season      = $this->season();

        $dungeonRoutes = DungeonRoute::query()
            ->with(['mappingVersion'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $index => $id) {
            $dungeonRoute = $dungeonRoutes->get((int)$id);
            if ($dungeonRoute === null || in_array($dungeonRoute->id, $memberIds, true)) {
                continue;
            }

            $mappingVersion = $dungeonRoute->mappingVersion;
            if ($mappingVersion === null || $gameVersion === null || $mappingVersion->game_version_id !== $gameVersion->id) {
                $validator->errors()->add(
                    sprintf('dungeon_routes.%d', $index),
                    __('validation.custom.collection_dungeon_routes.game_version'),
                );
            } elseif ($season !== null && $dungeonRoute->season_id !== $season->id) {
                $validator->errors()->add(
                    sprintf('dungeon_routes.%d', $index),
                    __('validation.custom.collection_dungeon_routes.season'),
                );
            }
        }
    }

    /**
     * An active game version, or the one the collection already has - the backfill may have given it an inactive one.
     */
    private function gameVersionExistsRule(): Exists
    {
        $currentGameVersionId = $this->existingDungeonRouteCollection()?->game_version_id;

        return Rule::exists('game_versions', 'id')->where(static function (Builder $query) use ($currentGameVersionId): void {
            $query->where('active', 1);

            if ($currentGameVersionId !== null) {
                $query->orWhere('id', $currentGameVersionId);
            }
        });
    }

    private function existingDungeonRouteCollection(): ?DungeonRouteCollection
    {
        $dungeonRouteCollection = $this->route('dungeonRouteCollection');

        return $dungeonRouteCollection instanceof DungeonRouteCollection ? $dungeonRouteCollection : null;
    }

    /**
     * The id whose own routes/teams the request must validate against - the collection's actual
     * owner when editing an existing one (an admin may be acting on someone else's collection),
     * or the current user when creating a brand new one.
     */
    private function dungeonRouteCollectionOwnerId(): int
    {
        $dungeonRouteCollection = $this->existingDungeonRouteCollection();

        return $dungeonRouteCollection !== null ? $dungeonRouteCollection->user_id : (Auth::id() ?? 0);
    }
}
