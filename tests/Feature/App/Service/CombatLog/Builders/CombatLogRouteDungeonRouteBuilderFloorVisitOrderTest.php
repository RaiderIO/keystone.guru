<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Repositories\Stub\DungeonRoute\DungeonRouteAffixGroupRepository;
use App\Repositories\Stub\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Stub\KillZone\KillZoneEnemyRepository;
use App\Repositories\Stub\KillZone\KillZoneRepository;
use App\Repositories\Stub\KillZone\KillZoneSpellRepository;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\TestCases\PublicTestCase;

/**
 * Magisters' Terrace (Midnight) sends the party back through the Grand Magister's Asylum (ui_map_id 2516) and the
 * Magisters' Terrace (2511) in a Void phase, mapped as floors of their own that share those ui_map_ids. The Void
 * floors and the tower floors above them also share npc_ids at overlapping ingame X/Y. The fixtures are real runs,
 * anonymised and stripped of spells and player deaths.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class CombatLogRouteDungeonRouteBuilderFloorVisitOrderTest extends PublicTestCase
{
    use LoadsJsonFiles;

    private const string FIXTURE_VOID_PHASE = 'Midnight/midnight_s1_magisters_terrace_20_void_phase';

    private const string FIXTURE_VOID_ASYLUM = 'Midnight/midnight_s1_magisters_terrace_22_void_asylum';

    private const string FIXTURE_ROOT_PATH = '../../../../Controller/Api/V1/APICombatLogController/';

    private const int UI_MAP_ID_MAGISTERS_TERRACE = 2511;

    private const int UI_MAP_ID_GRAND_MAGISTER_ASYLUM = 2516;

    private const string FLOOR_NAME_MAGISTERS_TERRACE_VOID = 'dungeons.midnight.magisters_terrace_midnight.floors.magisters_terrace_void';

    #[Test]
    #[DataProvider('magistersTerraceRunProvider')]
    public function build_givenTerraceRevisitedInTheVoidPhase_resolvesThoseKillsOntoTheVoidTerrace(string $fixtureName): void
    {
        // Arrange
        $voidTerraceFloor = Floor::query()->where('name', self::FLOOR_NAME_MAGISTERS_TERRACE_VOID)->firstOrFail();
        $combatLogRoute   = $this->createCombatLogRoute($fixtureName);

        // Act
        $this->createBuilder($combatLogRoute)->build();

        // Assert
        $voidPhaseTerraceNpcs = $this->getResolvedNpcsInEngagedOrder($combatLogRoute)
            ->skipUntil(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->coord->uiMapId === self::UI_MAP_ID_GRAND_MAGISTER_ASYLUM)
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->coord->uiMapId === self::UI_MAP_ID_MAGISTERS_TERRACE);

        $this->assertGreaterThan(20, $voidPhaseTerraceNpcs->count(), 'Precondition: the fixture kills its way back through the terrace in the Void phase');

        $npcsOffTheVoidTerrace = $voidPhaseTerraceNpcs
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy()->floor_id !== $voidTerraceFloor->id)
            ->map(fn(CombatLogRouteNpcRequestDto $npc) => $this->describeResolution($npc))
            ->values()
            ->all();

        $this->assertSame([], $npcsOffTheVoidTerrace);
    }

    #[Test]
    #[DataProvider('magistersTerraceRunProvider')]
    public function build_givenStackedFloorsSharingNpcIds_resolvesEveryKillOntoAFloorOfItsLoggedUiMapId(string $fixtureName): void
    {
        // Arrange
        $combatLogRoute = $this->createCombatLogRoute($fixtureName);

        // Act
        $this->createBuilder($combatLogRoute)->build();

        // Assert
        $resolvedNpcs = $this->getResolvedNpcsInEngagedOrder($combatLogRoute);

        $this->assertGreaterThan(150, $resolvedNpcs->count(), 'Precondition: most of the fixture\'s kills resolve to an enemy');

        $npcsOnAnotherUiMapId = $resolvedNpcs
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy()->floor->ui_map_id !== $npc->coord->uiMapId)
            ->map(fn(CombatLogRouteNpcRequestDto $npc) => $this->describeResolution($npc))
            ->values()
            ->all();

        $this->assertSame([], $npcsOnAnotherUiMapId);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function magistersTerraceRunProvider(): array
    {
        return [
            '+20, Void phase'  => [self::FIXTURE_VOID_PHASE],
            '+22, Void Asylum' => [self::FIXTURE_VOID_ASYLUM],
        ];
    }

    private function createCombatLogRoute(string $fixtureName): CombatLogRouteRequestDto
    {
        return CombatLogRouteRequestDto::createFromArray(
            $this->getJsonData($fixtureName, self::FIXTURE_ROOT_PATH),
        );
    }

    /**
     * Writes nothing: every write-side repository is a stub.
     */
    private function createBuilder(CombatLogRouteRequestDto $combatLogRoute): CombatLogRouteDungeonRouteBuilder
    {
        return new CombatLogRouteDungeonRouteBuilder(
            app(SeasonServiceInterface::class),
            app(SeasonAffixGroupServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            new DungeonRouteRepository(),
            new DungeonRouteAffixGroupRepository(),
            new KillZoneRepository(),
            new KillZoneEnemyRepository(),
            new KillZoneSpellRepository(),
            app(EnemyRepositoryInterface::class),
            app(NpcRepositoryInterface::class),
            app(SpellRepositoryInterface::class),
            app(FloorRepositoryInterface::class),
            app(DungeonRepositoryInterface::class),
            $combatLogRoute,
        );
    }

    /**
     * @return Collection<int, CombatLogRouteNpcRequestDto>
     */
    private function getResolvedNpcsInEngagedOrder(CombatLogRouteRequestDto $combatLogRoute): Collection
    {
        return $combatLogRoute->npcs
            ->sortBy(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getEngagedAt()->getTimestampMs())
            ->filter(static fn(CombatLogRouteNpcRequestDto $npc) => $npc->getResolvedEnemy() !== null)
            ->values();
    }

    private function describeResolution(CombatLogRouteNpcRequestDto $npc): string
    {
        $resolvedFloor = $npc->getResolvedEnemy()->floor;

        return sprintf(
            '%s logged on ui_map_id %d resolved onto floor %d (index %d, ui_map_id %d)',
            $npc->getUniqueId(),
            $npc->coord->uiMapId,
            $resolvedFloor->id,
            $resolvedFloor->index,
            $resolvedFloor->ui_map_id,
        );
    }
}
