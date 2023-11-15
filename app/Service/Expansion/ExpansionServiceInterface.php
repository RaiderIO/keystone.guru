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
     * @param Carbon           $carbon
     * @param GameServerRegion $gameServerRegion
     * @return Expansion|null
     */
    public function getExpansionAt(Carbon $carbon, GameServerRegion $gameServerRegion): ?Expansion;

    /**
     * @param GameServerRegion $gameServerRegion
     * @return Expansion
     */
    public function getCurrentExpansion(GameServerRegion $gameServerRegion): Expansion;

    /**
     * @param GameServerRegion $gameServerRegion
     * @return Expansion|null
     */
    public function getNextExpansion(GameServerRegion $gameServerRegion): ?Expansion;

    /**
     * @param Expansion        $expansion
     * @param GameServerRegion $gameServerRegion
     * @return ExpansionData
     */
    public function getData(Expansion $expansion, GameServerRegion $gameServerRegion): ExpansionData;

    /**
     * @param Expansion        $expansion
     * @param GameServerRegion $gameServerRegion
     * @return Season|null
     */
    public function getCurrentSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

    /**
     * @param Expansion        $expansion
     * @param GameServerRegion $gameServerRegion
     * @return Season|null
     */
    public function getNextSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

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
     * @param Expansion        $expansion
     * @param GameServerRegion $gameServerRegion
     * @return Collection
     */
    public function getCurrentSeasonAffixGroups(Expansion $expansion, GameServerRegion $gameServerRegion): Collection;
}
