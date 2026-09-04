<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('AnalyzeEnemyFailuresCommand')]
final class AnalyzeEnemyFailuresCommandTest extends PublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private Floor $floor;

    /** @var array<int, int> */
    private array $createdFailureIds = [];

    /** @var array<int, int> */
    private array $createdNpcEnemyForcesIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        [$this->dungeon, $this->mappingVersion] = $this->findDungeon(facadeEnabled: false, constraint: static function (Builder $query): void {
            $query->whereHas('floors', static fn(Builder $floors) => $floors->where('facade', 0)->where('ingame_max_x', '!=', 0));
        });

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('facade', 0)->where('ingame_max_x', '!=', 0)->firstOrFail();
        $this->floor = $floor;
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyFailure::query()->whereIn('id', $this->createdFailureIds)->delete();

            if ($this->createdNpcEnemyForcesIds !== []) {
                NpcEnemyForces::query()->whereKey($this->createdNpcEnemyForcesIds)->delete();
                new NpcEnemyForces()->flushCache();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenUnknownDungeonKey_returnsFailure(): void
    {
        $this->artisan('combatlog:analyzeenemyfailures', ['dungeon' => 'no-such-dungeon-key'])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenUnknownFormat_returnsFailure(): void
    {
        $this->artisan('combatlog:analyzeenemyfailures', ['dungeon' => $this->dungeon->key, '--format' => 'xml'])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenFormatMarkdown_outputsTableWithTheCluster(): void
    {
        // Arrange — only npcs worth enemy forces are analysed at all
        $npcId = 99960;
        $this->createEnemyForces($npcId);

        for ($i = 0; $i < 6; $i++) {
            $this->createdFailureIds[] = CombatLogRouteEnemyFailure::create([
                'dungeon_route_id'   => 7000 + $i,
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $npcId,
                'lat'                => -100.0 + $i * 0.01,
                'lng'                => 150.0,
            ])->id;
        }

        // Act + Assert
        $this->artisan('combatlog:analyzeenemyfailures', [
            'dungeon'           => $this->dungeon->key,
            '--mapping-version' => $this->mappingVersion->id,
            '--format'          => 'markdown',
        ])
            ->expectsOutputToContain('| # | Verdict | NPC |')
            ->expectsOutputToContain(sprintf('(%d)', $npcId))
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenFormatJsonAndHideLowVolume_leavesLowVolumeClustersOut(): void
    {
        // Arrange — two failures on one route: low volume
        $npcId = 99961;
        $this->createEnemyForces($npcId);

        for ($i = 0; $i < 2; $i++) {
            $this->createdFailureIds[] = CombatLogRouteEnemyFailure::create([
                'dungeon_route_id'   => 7100,
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $npcId,
                'lat'                => -100.0,
                'lng'                => 150.0,
            ])->id;
        }

        // Act + Assert
        $this->artisan('combatlog:analyzeenemyfailures', [
            'dungeon'           => $this->dungeon->key,
            '--mapping-version' => $this->mappingVersion->id,
            '--format'          => 'json',
            '--hide-low-volume' => true,
        ])
            ->doesntExpectOutputToContain(sprintf('"npc_id": %d', $npcId))
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenFormatJson_outputsClusterData(): void
    {
        $this->artisan('combatlog:analyzeenemyfailures', ['dungeon' => $this->dungeon->key, '--format' => 'json'])
            ->expectsOutputToContain('"cluster_radius_yd"')
            ->assertSuccessful();
    }

    private function createEnemyForces(int $npcId, int $enemyForces = 10): void
    {
        $this->createdNpcEnemyForcesIds[] = NpcEnemyForces::query()->create([
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => $npcId,
            'enemy_forces'       => $enemyForces,
        ])->id;
    }
}
