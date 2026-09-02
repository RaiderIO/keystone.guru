<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Models\Laratrust\Role;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminToolsCombatLogRegenerateRequest extends FormRequest
{
    /** The dungeon select's value for "every dungeon there is". */
    private const string DUNGEON_ID_ALL = '-1';

    /** Prefix of the dungeon select's value when a whole season's worth of dungeons is selected. */
    private const string DUNGEON_ID_SEASON_PREFIX = 'season-';

    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * The dungeon select (common.dungeon.select) submits either a dungeon id, -1 for "all dungeons",
     * or `season-<id>` when a whole season is picked - hence the two shapes of rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $dungeonId = $this->input('dungeon_id');

        return [
            'dungeon_id' => is_numeric($dungeonId) && (string)$dungeonId !== self::DUNGEON_ID_ALL
                ? ['required', 'integer', 'exists:dungeons,id']
                : ['required', 'string', sprintf('regex:/^(%s|%s\d+)$/', self::DUNGEON_ID_ALL, self::DUNGEON_ID_SEASON_PREFIX)],
            'season_id'             => ['nullable', 'integer', 'exists:seasons,id'],
            'periods'               => ['nullable', 'array'],
            'periods.*'             => ['integer', 'min:0'],
            'delete_enemy_failures' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A `season-<id>` dungeon selection cannot carry an `exists` rule of its own, so the season it names is
     * checked here - otherwise a season that does not exist would only be caught by getDungeonIds()'s
     * findOrFail(), which answers a bare 404 instead of a validation error.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $dungeonId = (string)$this->input('dungeon_id');

                if (!Str::startsWith($dungeonId, self::DUNGEON_ID_SEASON_PREFIX)) {
                    return;
                }

                $seasonId = (int)Str::after($dungeonId, self::DUNGEON_ID_SEASON_PREFIX);
                if (!Season::query()->where('id', $seasonId)->exists()) {
                    // 'dungeon id' is what Laravel itself derives from the field name for every other rule
                    $validator->errors()->add('dungeon_id', __('validation.exists', ['attribute' => 'dungeon id']));
                }
            },
            // A season and a set of weeks that do not overlap select nothing at all, which is indistinguishable
            // from a regeneration that simply found no routes - so refuse the combination instead
            function (Validator $validator): void {
                $seasonId = $this->input('season_id');
                $periods  = $this->input('periods');

                if (empty($seasonId) || !is_array($periods) || $periods === []) {
                    return;
                }

                $season = Season::query()->find((int)$seasonId);
                if ($season === null) {
                    return;
                }

                $seasonPeriods = app(SeasonServiceInterface::class)
                    ->getWeeklyPeriods($season, self::getPeriodRegion())
                    ->values();

                $outsideSeason = collect($periods)
                    ->map(static fn($period): int => (int)$period)
                    ->diff($seasonPeriods);

                if ($outsideSeason->isNotEmpty()) {
                    $validator->errors()->add('periods', __('validation.custom.periods.not_in_season', [
                        'season' => $season->name_long,
                    ]));
                }
            },
        ];
    }

    /**
     * The dungeons the regeneration should be limited to, or null when it should not be limited at all.
     *
     * @return Collection<int, int>|null
     */
    public function getDungeonIds(): ?Collection
    {
        return once(function (): ?Collection {
            $dungeonId = (string)$this->validated('dungeon_id');

            if ($dungeonId === self::DUNGEON_ID_ALL) {
                return null;
            }

            if (Str::startsWith($dungeonId, self::DUNGEON_ID_SEASON_PREFIX)) {
                return Season::query()
                    ->findOrFail((int)Str::after($dungeonId, self::DUNGEON_ID_SEASON_PREFIX))
                    ->dungeons
                    ->pluck('id');
            }

            return collect([Dungeon::query()->findOrFail((int)$dungeonId)->id]);
        });
    }

    /**
     * The season the regenerated routes must have been created in, or null when any season goes.
     */
    public function getSeason(): ?Season
    {
        return once(function (): ?Season {
            $seasonId = $this->validated('season_id');

            if ($seasonId === null || $seasonId === '') {
                return null;
            }

            return Season::query()->findOrFail((int)$seasonId);
        });
    }

    /**
     * The keystone leaderboard periods (weeks) the regenerated routes must have been run in, or null when
     * any week goes.
     *
     * @return Collection<int, int>|null
     */
    public function getPeriods(): ?Collection
    {
        return once(function (): ?Collection {
            $periods = $this->validated('periods');

            if (!is_array($periods) || $periods === []) {
                return null;
            }

            return collect($periods)
                ->map(static fn($period): int => (int)$period)
                ->unique()
                ->values();
        });
    }

    /**
     * Leaderboard periods are region specific: the same run is one period apart depending on which region's
     * weekly reset it is counted from. The week select is built from - and validated against - the default
     * region, the same one Season::start_period and the heatmap's week filter use.
     */
    public static function getPeriodRegion(): GameServerRegion
    {
        return GameServerRegion::query()->where('short', GameServerRegion::DEFAULT_REGION)->firstOrFail();
    }

    /**
     * Whether recorded ARC enemy failures should be deleted for the dungeon(s) being regenerated.
     */
    public function deleteEnemyFailures(): bool
    {
        return $this->boolean('delete_enemy_failures');
    }
}
