<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            'shrouded_bounty_type'           => SimulationCraftRaidEventsOptions::SHROUDED_BOUNTY_TYPE_NONE,
            'affix'                          => [SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED],
            'thundering_clear_seconds'       => 0,
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
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    /**
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider')]
    public function simulate_givenInvalidField_returnsUnprocessableEntity(array $override): void
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
    }

    /**
     * @return array<string, list<array<string, int|list<string>|string|null>>>
     */
    public static function simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider(): array
    {
        // raid_buffs_mask max = 2 ** (count(SimulationCraftRaidBuffs::cases()) - 1) = 2 ** 9 = 512
        return [
            'missing key_level'                            => [['key_level' => null]],
            'key_level above max (40)'                     => [['key_level' => 41]],
            'missing shrouded_bounty_type'                 => [['shrouded_bounty_type' => null]],
            'invalid shrouded_bounty_type'                 => [['shrouded_bounty_type' => 'invalid']],
            'invalid affix item'                           => [['affix' => ['invalid_affix']]],
            'missing thundering_clear_seconds'             => [['thundering_clear_seconds' => null]],
            'thundering_clear_seconds above max (15)'      => [['thundering_clear_seconds' => 16]],
            'missing raid_buffs_mask'                      => [['raid_buffs_mask' => null]],
            'raid_buffs_mask above max (1024)'             => [['raid_buffs_mask' => 1205]],
            'missing hp_percent'                           => [['hp_percent' => null]],
            'missing ranged_pull_compensation_yards'       => [['ranged_pull_compensation_yards' => null]],
            'invalid use_mounts (not 0 or 1)'              => [['use_mounts' => 2]],
            'non-integer simulate_bloodlust_per_pull item' => [['simulate_bloodlust_per_pull' => ['not-an-int']]],
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
}
