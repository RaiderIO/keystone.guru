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
    public function getExpansionAt(Carbon $carbon, GameServerRegion $gameServerRegion): ?Expansion;

    public function getCurrentExpansion(GameServerRegion $gameServerRegion): Expansion;

    public function getNextExpansion(GameServerRegion $gameServerRegion): ?Expansion;

    public function getData(Expansion $expansion, GameServerRegion $gameServerRegion): ExpansionData;

    public function getCurrentSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

    public function getNextSeason(Expansion $expansion, GameServerRegion $gameServerRegion): ?Season;

    public function getActiveDungeons(Expansion $expansion): Collection;

    public function getCurrentAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    public function getNextAffixGroup(Expansion $expansion, GameServerRegion $gameServerRegion): ?AffixGroup;

    public function getCurrentSeasonAffixGroups(Expansion $expansion, GameServerRegion $gameServerRegion): Collection;
}
