<?php

namespace App\Service\DungeonRoute;

use App\Models\AffixGroup\AffixGroupBase;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class DevDiscoverService extends BaseDiscoverService
{
    /**
     * Gets a builder that provides a template for popular routes.
     */
    private function popularBuilder(): Builder
    {
        $this->ensureGameVersion();

        return DungeonRoute::query()->limit(8)
            ->when($this->closure !== null, $this->closure)
            ->select('dungeon_routes.*')
            ->with([
                'author',
                'affixes',
                'ratings',
                'mappingVersion',
                'thumbnails',
                'dungeon' => fn(BelongsTo $query) => $query->without(['gameVersion']),
                'season'  => fn(BelongsTo $query) => $query->without([
                    'affixGroups',
                    'dungeons',
                ]),
            ])
            ->without([
                'faction',
                'specializations',
                'classes',
                'races',
            ])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            ->when($this->season === null, function (Builder $builder) {
                $builder->where('mapping_versions.game_version_id', $this->gameVersion->id);
            })
            ->when($this->season !== null, function (Builder $builder) {
                $builder->join('season_dungeons', 'season_dungeons.dungeon_id', '=', 'dungeons.id')
                    ->where('season_dungeons.season_id', $this->season->id);
            })
//            ->where('dungeon_routes.published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->whereNull('dungeon_routes.expires_at')
            ->where('demo', false);
    }

    /**
     * Gets a builder that provides a template for popular routes.
     */
    private function newBuilder(): Builder
    {
        $this->ensureGameVersion();

        return DungeonRoute::query()->limit(8)
            ->when($this->closure !== null, $this->closure)
            ->select('dungeon_routes.*')
            ->with([
                'author',
                'affixes',
                'ratings',
                'mappingVersion',
                'thumbnails',
                'dungeon' => fn(BelongsTo $query) => $query->without(['gameVersion']),
                'season'  => fn(BelongsTo $query) => $query->without([
                    'affixGroups',
                    'dungeons',
                ]),
            ])
            ->without([
                'faction',
                'specializations',
                'classes',
                'races',
            ])
            ->join('dungeons', 'dungeon_routes.dungeon_id', '=', 'dungeons.id')
            ->join('mapping_versions', 'mapping_versions.id', 'dungeon_routes.mapping_version_id')
            ->when($this->season === null, function (Builder $builder) {
                $builder->where('mapping_versions.game_version_id', $this->gameVersion->id);
            })
            ->when($this->season !== null, function (Builder $builder) {
                $builder->join('season_dungeons', 'season_dungeons.dungeon_id', '=', 'dungeons.id')
                    ->where('season_dungeons.season_id', $this->season->id);
            })
//            ->where('dungeon_routes.published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->whereNull('dungeon_routes.expires_at')
            ->where('demo', false)
            ->orderBy('published_at', 'desc');
    }

    /**
     * {@inheritDoc}
     */
    public function popular(): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularGroupedByDungeon(): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularGroupedByDungeonByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularByDungeon(Dungeon $dungeon): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    public function popularBySeason(Season $season): Collection
    {
        return $this->popularBuilder()->get();
    }

    public function popularBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection
    {
        return $this->popularBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function new(): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function newByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function newByDungeon(Dungeon $dungeon): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function newByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        return $this->newBuilder()->get();
    }

    public function newBySeason(Season $season): Collection
    {
        return $this->newBuilder()->get();
    }

    public function newBySeasonAndAffixGroup(Season $season, AffixGroupBase $affixGroup): Collection
    {
        return $this->newBuilder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function popularUsers(): Collection
    {
        // TODO: Implement popularUsers() method.
        return collect();
    }

    /**
     * {@inheritDoc}
     */
    public function popularUsersByAffixGroup(AffixGroupBase $affixGroup): Collection
    {
        // TODO: Implement popularUsersByAffixGroup() method.
        return collect();
    }

    /**
     * {@inheritDoc}
     */
    public function popularUsersByDungeon(Dungeon $dungeon): Collection
    {
        // TODO: Implement popularUsersByDungeon() method.
        return collect();
    }

    /**
     * {@inheritDoc}
     */
    public function popularUsersByDungeonAndAffixGroup(Dungeon $dungeon, AffixGroupBase $affixGroup): Collection
    {
        // TODO: Implement popularUsersByDungeonAndAffixGroup() method.
        return collect();
    }
}
