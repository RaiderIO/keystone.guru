<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class DungeonRouteCollectionIndexFormRequest extends FormRequest
{
    /**
     * The season filter value that selects the free-form collections.
     */
    public const string SEASON_NONE = 'none';

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
        return [
            'game_version_id' => [
                'nullable',
                'integer',
                Rule::exists('game_versions', 'id')->whereIn('id', $this->getSelectableGameVersionIds()),
            ],
            // Empty for every collection, SEASON_NONE for the free-form ones, or a season id
            'season' => [
                'nullable',
                Rule::when(
                    static fn(Fluent $input): bool => $input->get('season') !== self::SEASON_NONE,
                    ['integer', Rule::exists('seasons', 'id')],
                ),
            ],
        ];
    }

    /**
     * The game versions the overview may filter on: every active one, plus any the user's own collections carry.
     *
     * @return Collection<int, GameVersion>
     */
    public function selectableGameVersions(): Collection
    {
        return once(fn(): Collection => GameVersion::query()
            ->whereIn('id', $this->getSelectableGameVersionIds())
            ->orderBy('id')
            ->get());
    }

    /**
     * The game version to list collections of: the requested one, or the user's current one.
     */
    public function gameVersion(): GameVersion
    {
        return once(function (): GameVersion {
            $gameVersionId = $this->validated('game_version_id');

            if ($gameVersionId === null) {
                return GameVersion::getUserOrDefaultGameVersion();
            }

            return GameVersion::query()->findOrFail((int)$gameVersionId);
        });
    }

    /**
     * Whether only free-form collections are requested. Only meaningful on a game version with seasons.
     */
    public function isFreeFormOnly(): bool
    {
        return $this->gameVersion()->has_seasons && $this->validated('season') === self::SEASON_NONE;
    }

    /**
     * The season to list the sets of, or null for no season filter. Only meaningful on a game version with seasons.
     */
    public function season(): ?Season
    {
        return once(function (): ?Season {
            $season = $this->validated('season');

            if ($season === null || $season === self::SEASON_NONE || !$this->gameVersion()->has_seasons) {
                return null;
            }

            return Season::query()->findOrFail((int)$season);
        });
    }

    /**
     * @return array<int, int>
     */
    private function getSelectableGameVersionIds(): array
    {
        return once(function (): array {
            /** @var User|null $user */
            $user = $this->user();

            $ownGameVersionIds = $user?->dungeonRouteCollections()
                ->whereNotNull('game_version_id')
                ->distinct()
                ->pluck('game_version_id')
                ->all() ?? [];

            return GameVersion::query()
                ->where('active', 1)
                ->pluck('id')
                ->merge($ownGameVersionIds)
                ->map(static fn(mixed $id): int => (int)$id)
                ->unique()
                ->values()
                ->all();
        });
    }
}
