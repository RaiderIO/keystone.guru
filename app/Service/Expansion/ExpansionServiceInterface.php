<?php


namespace App\Service\Expansion;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ExpansionServiceInterface
{
    /**
     * @param Carbon $carbon
     * @return Expansion|null
     */
    public function getExpansionAt(Carbon $carbon): ?Expansion;

    /**
     * @return Expansion
     */
    public function getCurrentExpansion(): Expansion;

    /**
     * @param Expansion $expansion
     * @return ExpansionData
     */
    public function getData(Expansion $expansion): ExpansionData;

    /**
     * @param Expansion $expansion
     * @return Season
     */
    public function getCurrentSeason(Expansion $expansion): Season;

    /**
     * @param Expansion $expansion
     * @return Collection
     */
    public function getActiveDungeons(Expansion $expansion): Collection;

    /**
     * @param Expansion $expansion
     * @param GameServerRegion $gameServerRegion
     * @return AffixGroup|null
     */
    public function getCurrentAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    /**
     * @param Expansion $expansion
     * @param GameServerRegion $gameServerRegion
     * @return AffixGroup|null
     */
    public function getNextAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    /**
     * @param Expansion $expansion
     * @return Collection
     */
    public function getCurrentSeasonAffixGroups(Expansion $expansion): Collection;
}
