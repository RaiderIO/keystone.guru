<?php

namespace App\Service\Expansion;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExpansionService implements ExpansionServiceInterface
{
    /** @var Collection<string, Expansion> The current expansion, keyed by region short */
    private Collection $currentExpansionCache;

    /** @var Collection<string, Season|null> The current season, keyed by expansion id and region short */
    private Collection $currentSeasonCache;

    public function __construct()
    {
        $this->currentExpansionCache = collect();
        $this->currentSeasonCache    = collect();
    }

    /**
     * {@inheritDoc}
     */
    public function getExpansionAt(Carbon $carbon, ?GameServerRegion $gameServerRegion = null): ?Expansion
    {
        $gameServerRegion ??= GameServerRegion::getUserOrDefaultRegion();

        /** @var Expansion|null $expansion */
        $expansion = Expansion::whereRaw(
            'DATE_ADD(DATE_ADD(`released_at`, INTERVAL ? day), INTERVAL ? hour) < ?',
            [
                $gameServerRegion->reset_day_offset,
                $gameServerRegion->reset_hours_offset,
                $carbon,
            ],
        )->orderBy('released_at', 'desc')
            ->first();

        return $expansion;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentExpansion(?GameServerRegion $gameServerRegion = null): Expansion
    {
        $gameServerRegion ??= GameServerRegion::getUserOrDefaultRegion();

        // Called from all over a single request - the controller, the repositories and the view
        // composers each asked the database again for the same row (#4587)
        if (!$this->currentExpansionCache->has($gameServerRegion->short)) {
            $this->currentExpansionCache->put(
                $gameServerRegion->short,
                $this->getExpansionAt(Carbon::now(), $gameServerRegion),
            );
        }

        return $this->currentExpansionCache->get($gameServerRegion->short);
    }

    public function getNextExpansion(?GameServerRegion $gameServerRegion = null): ?Expansion
    {
        $currentExpansion = $this->getCurrentExpansion($gameServerRegion);
        $nextExpansion    = $this->getExpansionAt(Carbon::now()->addWeeks(12), $gameServerRegion);

        return $nextExpansion !== null && $nextExpansion->id !== $currentExpansion->id ? $nextExpansion : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(
        SeasonAffixGroupServiceInterface $seasonAffixGroupService,
        Expansion                        $expansion,
        ?GameServerRegion                $gameServerRegion = null,
    ): ExpansionData {
        return new ExpansionData($this, $seasonAffixGroupService, $expansion, $gameServerRegion);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentSeason(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?Season
    {
        $gameServerRegion ??= GameServerRegion::getUserOrDefaultRegion();

        // Expansion::currentSeason() memoises on the model instance, and a request holds several
        // instances of the same expansion row because of the default eager loads (#4587)
        $key = sprintf('%d-%s', $expansion->id, $gameServerRegion->short);

        if (!$this->currentSeasonCache->has($key)) {
            $this->currentSeasonCache->put($key, $expansion->currentSeason($gameServerRegion));
        }

        return $this->currentSeasonCache->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getNextSeason(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?Season
    {
        return $expansion->nextSeason($gameServerRegion);
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveDungeons(Expansion $expansion): Collection
    {
        return $expansion->dungeonsAndRaids;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getCurrentAffixGroup(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?AffixGroup
    {
        $season = $this->getCurrentSeason($expansion, $gameServerRegion);

        return $season !== null ? resolve(SeasonAffixGroupServiceInterface::class)->getCurrentAffixGroupInRegion($season, $gameServerRegion) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getNextAffixGroup(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?AffixGroup
    {
        $season = $this->getCurrentSeason($expansion, $gameServerRegion);

        return $season !== null ? resolve(SeasonAffixGroupServiceInterface::class)->getNextAffixGroupInRegion($season, $gameServerRegion) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentSeasonAffixGroups(
        Expansion         $expansion,
        ?GameServerRegion $gameServerRegion = null,
    ): Collection {
        $currentSeason = $this->getCurrentSeason($expansion, $gameServerRegion);

        return $currentSeason !== null ? $currentSeason->affixGroups()
            ->with(['affixes:affixes.id,affixes.key,affixes.name,affixes.description'])
            ->get() : collect();
    }
}
