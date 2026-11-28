<?php

namespace Tests\Feature\Controller\Ajax;

use App\Features\SimulationCraftBossMechanics;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\PublishedState;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('SimulationCraft')]
final class AjaxDungeonRouteSimulateControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Without Accept: application/json the ValidationException handler redirects (302)
        // instead of returning a JSON 422 response.
        $this->defaultHeaders['Accept'] = 'application/json';
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'key_level'                      => 20,
            'affix'                          => [SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED],
            'raid_buffs_mask'                => 0,
            'hp_percent'                     => 100,
            'ranged_pull_compensation_yards' => 0,
            'use_mounts'                     => 0,
            'simulate_bloodlust_per_pull'    => [],
        ];
    }

    private function simulateUrl(): string
    {
        return sprintf('/ajax/%s/simulate', $this->dungeonRoute->public_key);
    }

    #[Test]
    public function simulate_givenValidRequest_returnsOkWithString(): void
    {
        // Arrange - dungeon route set up by DungeonRouteTestBase

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $this->validPayload());

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['string']);
            $this->assertStringContainsString('fight_style=DungeonRoute', $response->json('string'));
            $this->assertStringContainsString('keystone_level=20', $response->json('string'));
            $this->assertStringNotContainsString('max_time=', $response->json('string'));
            $this->assertStringNotContainsString('override.bloodlust=', $response->json('string'));
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function simulate_givenShroudedAndThunderingFieldsFromAnOlderPage_ignoresThem(): void
    {
        // Arrange - a page loaded before the release still posts both fields
        $payload = array_merge($this->validPayload(), [
            'shrouded_bounty_type'     => 'crit',
            'thundering_clear_seconds' => 10,
        ]);

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $payload);

            // Assert
            $response->assertOk();
            $this->assertStringContainsString('fight_style=DungeonRoute', $response->json('string'));
            $this->assertStringNotContainsString('keystone_bounty=', $response->json('string'));
            $this->assertStringNotContainsString('mark_duration=', $response->json('string'));
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function simulate_givenBloodlustOnAsManyPullsAsARouteMayHave_storesThemAll(): void
    {
        // Arrange - kill zone ids run to ten digits
        $killZoneIds = range(2_000_000_001, 2_000_000_050);

        try {
            // Act
            $response = $this->post($this->simulateUrl(), array_merge($this->validPayload(), [
                'simulate_bloodlust_per_pull' => $killZoneIds,
            ]));

            // Assert
            $response->assertOk();
            $this->assertSame(
                implode(',', $killZoneIds),
                SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->firstOrFail()->simulate_bloodlust_per_pull,
            );
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function simulate_givenRouteUserMayNotView_returnsForbidden(): void
    {
        // Arrange - a sandbox route is viewable by anyone, so make it a real, unpublished one
        $viewer = User::factory()->create();
        $this->dungeonRoute->update([
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            $this->actingAs($viewer);

            // Act
            $response = $this->post($this->simulateUrl(), $this->validPayload());

            // Assert
            $response->assertForbidden();
            $response->assertJsonMissingPath('string');
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function simulate_givenAdmin_storesTheirUserIdAndTheAdvancedSimulationFields(): void
    {
        // Arrange - AjaxPublicTestCase acts as the admin, who holds every Patreon benefit
        $payload = array_merge($this->validPayload(), [
            'ranged_pull_compensation_yards' => 30,
            'use_mounts'                     => 1,
        ]);

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $payload);

            // Assert
            $response->assertOk();
            $options = SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->firstOrFail();
            $this->assertSame(1, $options->user_id);
            $this->assertSame(30, $options->ranged_pull_compensation_yards);
            $this->assertSame(1, (int)$options->use_mounts);
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function simulate_givenViewerWithoutAdvancedSimulation_storesTheirUserIdAndZeroesTheAdvancedSimulationFields(): void
    {
        // Arrange - the factory route is a sandbox route, which anyone may view
        $viewer  = User::factory()->create();
        $payload = array_merge($this->validPayload(), [
            'ranged_pull_compensation_yards' => 30,
            'use_mounts'                     => 1,
        ]);

        try {
            $this->actingAs($viewer);

            // Act
            $response = $this->post($this->simulateUrl(), $payload);

            // Assert
            $response->assertOk();
            $options = SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->firstOrFail();
            $this->assertSame($viewer->id, $options->user_id);
            $this->assertSame(0, $options->ranged_pull_compensation_yards);
            $this->assertSame(0, (int)$options->use_mounts);
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function simulate_givenGuest_storesNoUserIdAndZeroesTheAdvancedSimulationFields(): void
    {
        // Arrange - the factory route is a sandbox route, which anyone may view
        $payload = array_merge($this->validPayload(), [
            'ranged_pull_compensation_yards' => 30,
        ]);
        $this->actingAsGuest();

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $payload);

            // Assert
            $response->assertOk();
            $options = SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->firstOrFail();
            $this->assertNull($options->user_id);
            $this->assertSame(0, $options->ranged_pull_compensation_yards);
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    /**
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider')]
    public function simulate_givenInvalidField_returnsUnprocessableEntity(array $override, string $expectedErrorKey): void
    {
        // Arrange - merge override into the valid payload; a null value means the key should be absent
        $payload = $this->validPayload();
        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            } else {
                $payload[$key] = $value;
            }
        }

        // Act
        $response = $this->post($this->simulateUrl(), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([$expectedErrorKey]);
    }

    /**
     * @return array<string, array{0: array<string, int|list<int|string>|string|null>, 1: string}>
     */
    public static function simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider(): array
    {
        // raid_buffs_mask max = 2 ** (count(SimulationCraftRaidBuffs::cases()) - 1) = 2 ** 9 = 512
        return [
            'missing key_level'                            => [['key_level' => null], 'key_level'],
            'key_level above max (40)'                     => [['key_level' => 41], 'key_level'],
            'invalid affix item'                           => [['affix' => ['invalid_affix']], 'affix.0'],
            'missing raid_buffs_mask'                      => [['raid_buffs_mask' => null], 'raid_buffs_mask'],
            'raid_buffs_mask above max (1024)'             => [['raid_buffs_mask' => 1205], 'raid_buffs_mask'],
            'missing hp_percent'                           => [['hp_percent' => null], 'hp_percent'],
            'missing ranged_pull_compensation_yards'       => [['ranged_pull_compensation_yards' => null], 'ranged_pull_compensation_yards'],
            'invalid use_mounts (not 0 or 1)'              => [['use_mounts' => 2], 'use_mounts'],
            'non-integer simulate_bloodlust_per_pull item' => [['simulate_bloodlust_per_pull' => ['not-an-int']], 'simulate_bloodlust_per_pull.0'],
            'more simulate_bloodlust_per_pull than pulls'  => [['simulate_bloodlust_per_pull' => range(1, 51)], 'simulate_bloodlust_per_pull'],
        ];
    }

    /**
     * Guards #4586: an npc appearing in several pulls is hydrated as a separate Npc instance per
     * pull, and each one used to lazy-load its own npc_healths rows - the only lazy-load violation
     * the query sweep recorded, and an N+1 that scales with the number of pulls in the route.
     */
    #[Test]
    public function simulate_givenSameNpcInSeveralPulls_loadsNpcHealthsOnceForTheRoute(): void
    {
        // Arrange - several pulls, each holding an enemy of the same npc. The base route's dungeon is not required to
        // have an npc mapped three times, so the route is re-drawn from a dungeon that is.
        $this->dungeonRoute->delete();

        /** @var Collection<int, Enemy> $enemies */
        [$dungeon, $mappingVersion, $enemies] = $this->findDungeon(
            facadeEnabled: false,
            challengeMode: true,
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?Collection => $mappingVersion->enemies()
                ->whereNotNull('npc_id')
                ->get()
                ->groupBy('npc_id')
                ->first(static fn(Collection $enemies): bool => $enemies->count() >= 3),
        );

        $this->dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var Collection<int, KillZone> $killZones */
        $killZones = collect();
        $index     = 1;
        foreach ($enemies->take(3) as $enemy) {
            $killZones->push(
                KillZone::factory()
                    ->withEnemies($enemy)
                    ->create([
                        'dungeon_route_id' => $this->dungeonRoute->id,
                        'floor_id'         => $enemy->floor_id,
                        'lat'              => $enemy->lat,
                        'lng'              => $enemy->lng,
                        'index'            => $index++,
                    ]),
            );
        }

        /** @var array<int, string> $npcHealthQueries */
        $npcHealthQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$npcHealthQueries): void {
            if (str_contains($query->sql, 'from `npc_healths`')) {
                $npcHealthQueries[] = $query->sql;
            }
        });

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $this->validPayload());

            // Assert
            $response->assertOk();
            $this->assertLessThanOrEqual(
                1,
                count($npcHealthQueries),
                sprintf(
                    'Expected npc_healths to be eager-loaded once for the route, got: %s',
                    implode(' | ', $npcHealthQueries),
                ),
            );
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();

            foreach ($killZones as $killZone) {
                $killZone->killZoneEnemies()->delete();
                $killZone->delete();
            }
        }
    }

    #[Test]
    public function simulate_givenBossMechanicsWhileTheFeatureIsInactive_returnsTheStringWithoutThem(): void
    {
        // Arrange
        [$killZones, $mechanics] = $this->createRouteWithBossMechanics(3);
        Feature::deactivate(SimulationCraftBossMechanics::class);

        try {
            // Act
            $withMechanics = $this->post($this->simulateUrl(), $this->validPayload())->json('string');
            NpcEncounterMechanic::query()->whereIn('id', $mechanics->pluck('id'))->delete();
            $withoutMechanics = $this->post($this->simulateUrl(), $this->validPayload())->json('string');

            // Assert
            $this->assertIsString($withMechanics);
            $this->assertSame($withoutMechanics, $withMechanics);
        } finally {
            Feature::forget(SimulationCraftBossMechanics::class);
            $this->deleteRouteWithBossMechanics($killZones, $mechanics);
        }
    }

    #[Test]
    public function simulate_givenBossMechanicsWhileTheFeatureIsActive_loadsThemInOneQueryForTheWholeRoute(): void
    {
        // Arrange - three boss pulls, each with adds of their own
        [$killZones, $mechanics] = $this->createRouteWithBossMechanics(3);

        /** @var list<string> $mechanicQueries */
        $mechanicQueries = [];
        $queryCount      = 0;
        DB::listen(static function (QueryExecuted $query) use (&$mechanicQueries, &$queryCount): void {
            $queryCount++;
            if (str_contains($query->sql, 'from `npc_encounter_mechanics`')) {
                $mechanicQueries[] = $query->sql;
            }
        });

        try {
            Feature::deactivate(SimulationCraftBossMechanics::class);
            $this->post($this->simulateUrl(), $this->validPayload())->assertOk();
            $queryCountWithoutMechanics = $queryCount;
            Feature::activate(SimulationCraftBossMechanics::class);
            $queryCount = 0;

            // Act
            $response = $this->post($this->simulateUrl(), $this->validPayload());

            // Assert - the rows, and their add NPCs with their healths
            $response->assertOk();
            $this->assertSame(3, preg_match_all('/^raid_events\+=\/adds,pull=0[123],/m', (string)$response->json('string')));
            $this->assertCount(1, $mechanicQueries, implode(' | ', $mechanicQueries));
            $this->assertLessThanOrEqual($queryCountWithoutMechanics + 3, $queryCount);
        } finally {
            Feature::forget(SimulationCraftBossMechanics::class);
            $this->deleteRouteWithBossMechanics($killZones, $mechanics);
        }
    }

    /**
     * Re-draws the route as one pull per enemy of $pullCount different NPCs, and makes each of those NPCs the boss of an
     * encounter of its own with itself as attackable adds. Picks a dungeon without any mechanics of its own, so rows
     * built from ingested logs cannot add lines to the string.
     *
     * @return array{0: Collection<int, KillZone>, 1: Collection<int, NpcEncounterMechanic>}
     */
    private function createRouteWithBossMechanics(int $pullCount): array
    {
        $this->dungeonRoute->delete();

        /** @var Collection<int, Enemy> $enemies */
        [$dungeon, $mappingVersion, $enemies] = $this->findDungeon(
            facadeEnabled: false,
            challengeMode: true,
            constraint:    static fn(Builder $query): Builder => $query->whereNotIn('id', NpcEncounterMechanic::query()->select('dungeon_id')),
            resolve:       static function (Dungeon $dungeon, MappingVersion $mappingVersion) use ($pullCount): ?Collection {
                $enemies = $mappingVersion->enemies()
                    ->whereHas('npc', static fn(Builder $query): Builder => $query
                        ->where('classification_id', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL])
                        ->whereHas('npcHealths', static fn(Builder $query): Builder => $query->where('game_version_id', $mappingVersion->game_version_id)))
                    ->get()
                    ->unique('npc_id')
                    ->take($pullCount)
                    ->values();

                return $enemies->count() === $pullCount ? $enemies : null;
            },
        );

        $this->dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var Collection<int, KillZone> $killZones */
        $killZones = collect();
        /** @var Collection<int, NpcEncounterMechanic> $mechanics */
        $mechanics = collect();
        foreach ($enemies as $index => $enemy) {
            $killZones->push(
                KillZone::factory()
                    ->withEnemies($enemy)
                    ->create([
                        'dungeon_route_id' => $this->dungeonRoute->id,
                        'floor_id'         => $enemy->floor_id,
                        'lat'              => $enemy->lat,
                        'lng'              => $enemy->lng,
                        'index'            => $index + 1,
                    ]),
            );

            $encounter = [
                'dungeon_id'   => $dungeon->id,
                'encounter_id' => 990_001 + $index,
                'npc_id'       => $enemy->npc_id,
            ];
            $mechanics->push(NpcEncounterMechanic::factory()->create(array_merge($encounter, [
                'type'     => NpcEncounterMechanicType::Boss,
                'first_ms' => null,
                'count'    => null,
            ])));
            $mechanics->push(NpcEncounterMechanic::factory()->create(array_merge($encounter, [
                'type'        => NpcEncounterMechanicType::Adds,
                'duration_ms' => 5_000,
                'data'        => ['attackable' => true, 'summoned_by_boss' => true],
            ])));
        }

        return [$killZones, $mechanics];
    }

    /**
     * @param Collection<int, KillZone>             $killZones
     * @param Collection<int, NpcEncounterMechanic> $mechanics
     */
    private function deleteRouteWithBossMechanics(Collection $killZones, Collection $mechanics): void
    {
        SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        NpcEncounterMechanic::query()->whereIn('id', $mechanics->pluck('id'))->delete();

        foreach ($killZones as $killZone) {
            $killZone->killZoneEnemies()->delete();
            $killZone->delete();
        }
    }
}
