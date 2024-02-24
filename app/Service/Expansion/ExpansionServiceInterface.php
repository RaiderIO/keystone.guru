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
     * @return Expansion|null
     */
    public function getExpansionAt(Carbon $carbon, GameServerRegion $gameServerRegion): ?Expansion;

    /**
     * @return Expansion
     */
    public function getCurrentExpansion(GameServerRegion $gameServerRegion): Expansion;

    /**
     * @return Expansion|null
     */
    public function getNextExpansion(GameServerRegion $gameServerRegion): ?Expansion;

    /**
     * @return ExpansionData
     */
    public function getData(Expansion $expansion, GameServerRegion $gameServerRegion): ExpansionData;

    /**
     * @return Season|null
     */
    public function getCurrentSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

    /**
     * @return Season|null
     */
    public function getNextSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

    /**
     * @return Collection
     */
    public function getActiveDungeons(Expansion $expansion): Collection;

    /**
     * @return AffixGroup|null
     */
    public function getCurrentAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    /**
     * @return AffixGroup|null
     */
    public function getNextAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    /**
     * @return Collection
     */
    public function getCurrentSeasonAffixGroups(Expansion $expansion, GameServerRegion $gameServerRegion): Collection;
}
