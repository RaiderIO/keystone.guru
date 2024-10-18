<?php

namespace App\Service\Expansion;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ExpansionServiceInterface
{
    public function getExpansionAt(Carbon $carbon, ?GameServerRegion $gameServerRegion = null): ?Expansion;

    public function getCurrentExpansion(?GameServerRegion $gameServerRegion = null): Expansion;

    public function getNextExpansion(?GameServerRegion $gameServerRegion = null): ?Expansion;

    public function getData(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ExpansionData;

    public function getCurrentSeason(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?Season;

    public function getNextSeason(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?Season;

    public function getActiveDungeons(Expansion $expansion): Collection;

    public function getCurrentAffixGroup(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?AffixGroup;

    public function getNextAffixGroup(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): ?AffixGroup;

    public function getCurrentSeasonAffixGroups(Expansion $expansion, ?GameServerRegion $gameServerRegion = null): Collection;
}
