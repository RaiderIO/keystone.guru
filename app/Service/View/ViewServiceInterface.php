<?php

namespace App\Service\View;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\CharacterRace;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\Faction;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\RouteAttribute;
use App\Models\Season;
use App\Models\Spell\Spell;
use App\Service\Expansion\ExpansionData;
use Illuminate\Support\Collection;

interface ViewServiceInterface
{
    public function isLocal(): bool;

    public function isMapping(): bool;

    public function isProduction(): bool;

    /**
     * @return Collection<int, DungeonRoute>
     */
    public function getDemoRoutes(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDemoRouteDungeons(): Collection;

    /**
     * @return Collection<int, string>
     */
    public function getDemoRouteMapping(): Collection;

    /**
     * @return array{version: string, revision: string, nameAndVersion: string}
     */
    public function getAppVersionInfo(): array;

    public function getUserCount(): int;

    /**
     * @return Collection<int, GameServerRegion>
     */
    public function getAllRegions(): Collection;

    /**
     * @return Collection<int, Faction>
     */
    public function getAllFactions(): Collection;

    /**
     * @return Collection<int, CharacterClassSpecialization>
     */
    public function getCharacterClassSpecializations(): Collection;

    /**
     * @return Collection<int, CharacterClass>
     */
    public function getCharacterClasses(): Collection;

    /**
     * @return Collection<int, CharacterRace>
     */
    public function getCharacterRacesClasses(): Collection;

    /**
     * @return Collection<int, Affix>
     */
    public function getAllAffixes(): Collection;

    /**
     * @return Collection<int, RouteAttribute>
     */
    public function getAllRouteAttributes(): Collection;

    /**
     * @return Collection<int, PublishedState>
     */
    public function getAllPublishedStates(): Collection;

    /**
     * @return Collection<string, Collection<int, Spell>>
     */
    public function getSelectableSpellsByCategory(): Collection;

    /**
     * @return Collection<int, GameVersion>
     */
    public function getAllGameVersions(): Collection;

    /**
     * @return Collection<int, Expansion>
     */
    public function getActiveExpansions(): Collection;

    /**
     * @return Collection<int, Expansion>
     */
    public function getAllExpansions(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getDungeonsByExpansionIdDesc(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getRaidsByExpansionIdDesc(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getActiveDungeonsByExpansionIdDesc(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getActiveRaidsByExpansionIdDesc(): Collection;

    public function getSiegeOfBoralus(): ?Dungeon;

    /**
     * @return Collection<int, Collection<int, mixed>>
     */
    public function getAffixGroupEaseTiersByAffixGroup(): Collection;

    /**
     * @return Collection<int, string>
     */
    public function getDungeonExpansions(): Collection;

    /**
     * @return Collection<int, Dungeon>
     */
    public function getAllSpeedrunDungeons(): Collection;

    /**
     * @return Collection<int, Collection<int, array{id: int, text: string}>>
     */
    public function getDungeonStartsByDungeonId(): Collection;

    public function warmGlobalCaches(): void;

    public function getCurrentExpansionForRegion(GameServerRegion $gameServerRegion): Expansion;

    public function getCurrentSeasonForRegion(GameServerRegion $gameServerRegion): ?Season;

    public function getNextSeasonForRegion(GameServerRegion $gameServerRegion): ?Season;

    /**
     * @return Collection<string, ExpansionData>
     */
    public function getExpansionsData(GameServerRegion $gameServerRegion): Collection;

    /**
     * @return Collection<int, AffixGroup>
     */
    public function getAllAffixGroupsForRegion(GameServerRegion $gameServerRegion): Collection;

    /**
     * @return Collection<string, AffixGroup|null>
     */
    public function getAllCurrentAffixesForRegion(GameServerRegion $gameServerRegion): Collection;

    /**
     * @return Collection<string, Collection<int, AffixGroup>>
     */
    public function getAllAffixGroupsByActiveExpansion(GameServerRegion $gameServerRegion): Collection;

    /**
     * @return Collection<string, Collection<int, Affix>>
     */
    public function getFeaturedAffixesByActiveExpansion(GameServerRegion $gameServerRegion): Collection;

    /**
     * @param bool $useCache True to use the cache, false to regenerate it.
     *
     * @return array<string, mixed>
     */
    public function getGameServerRegionViewVariables(GameServerRegion $gameServerRegion, bool $useCache = true): array;

    public function shouldLoadViewVariables(string $pathInfo): bool;
}
