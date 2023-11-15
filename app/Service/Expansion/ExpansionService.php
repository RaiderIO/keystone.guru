<?php


namespace App\Service\Expansion;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class ExpansionService implements ExpansionServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getExpansionAt(Carbon $carbon, GameServerRegion $gameServerRegion): ?Expansion
    {
        /** @var Expansion|null $expansion */
        $expansion = Expansion::whereRaw('DATE_ADD(DATE_ADD(`released_at`, INTERVAL ? day), INTERVAL ? hour) < ?',
            [$gameServerRegion->reset_day_offset, $gameServerRegion->reset_hours_offset, $carbon]
        )->orderBy('released_at', 'desc')
            ->first();

        return $expansion;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentExpansion(GameServerRegion $gameServerRegion): Expansion
    {
        return $this->getExpansionAt(Carbon::now(), $gameServerRegion);
    }

    /**
     * @param GameServerRegion $gameServerRegion
     * @return Expansion|null
     */
    public function getNextExpansion(GameServerRegion $gameServerRegion): ?Expansion
    {
        return $this->getExpansionAt(Carbon::now()->addWeeks(4), $gameServerRegion);
    }

    /**
     * @inheritDoc
     */
    public function getData(Expansion $expansion, GameServerRegion $gameServerRegion): ExpansionData
    {
        return new ExpansionData($this, $expansion, $gameServerRegion);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season
    {
        return $expansion->currentSeason($gameServerRegion);
    }

    /**
     * @inheritDoc
     */
    public function getNextSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season
    {
        return $expansion->nextSeason($gameServerRegion);
    }

    /**
     * @inheritDoc
     */
    public function getActiveDungeons(Expansion $expansion): Collection
    {
        return $expansion->dungeons;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCurrentAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup
    {
        return optional($this->getCurrentSeason($expansion, $gameServerRegion))->getCurrentAffixGroupInRegion($gameServerRegion);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getNextAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup
    {
        return optional($this->getCurrentSeason($expansion, $gameServerRegion))->getNextAffixGroupInRegion($gameServerRegion);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSeasonAffixGroups(Expansion $expansion, GameServerRegion $gameServerRegion): Collection
    {
        $currentSeason = $this->getCurrentSeason($expansion, $gameServerRegion);

        return $currentSeason !== null ? $currentSeason->affixgroups()
            ->with(['affixes:affixes.id,affixes.key,affixes.name,affixes.description'])
            ->get() : collect();
    }
}
