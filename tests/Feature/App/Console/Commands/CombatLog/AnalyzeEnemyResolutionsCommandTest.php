<?php

namespace Tests\Feature\App\Console\Commands\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('AnalyzeEnemyResolutionsCommand')]
final class AnalyzeEnemyResolutionsCommandTest extends PublicTestCase
{
    use ProvidesDungeon;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private Floor $floor;

    /** @var array<int, int> */
    private array $createdResolutionIds = [];

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
            CombatLogRouteEnemyResolution::query()->whereIn('id', $this->createdResolutionIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenUnknownDungeonKey_returnsFailure(): void
    {
        $this->artisan('combatlog:analyzeenemyresolutions', ['dungeon' => 'no-such-dungeon-key'])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenUnknownMappingVersion_returnsFailure(): void
    {
        $this->artisan('combatlog:analyzeenemyresolutions', ['dungeon' => $this->dungeon->key, '--mapping-version' => 999999999])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenUnknownFormat_returnsFailure(): void
    {
        $this->artisan('combatlog:analyzeenemyresolutions', ['dungeon' => $this->dungeon->key, '--format' => 'xml'])
            ->assertFailed();
    }

    #[Test]
    public function handle_givenFormatMarkdown_outputsTableWithTheGroup(): void
    {
        // Arrange - an enemy id no seeded enemy has, so it forms its own packless group
        $enemyId = 999999901;
        $this->createResolutions($enemyId, 6);

        // Act + Assert
        $this->artisan('combatlog:analyzeenemyresolutions', [
            'dungeon'           => $this->dungeon->key,
            '--mapping-version' => $this->mappingVersion->id,
            '--format'          => 'markdown',
        ])
            ->expectsOutputToContain('| # | Verdict | Pack |')
            ->expectsOutputToContain((string)$enemyId)
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenFormatJsonAndHideLowVolume_leavesLowVolumeGroupsOut(): void
    {
        // Arrange - one route only: low volume
        $enemyId = 999999902;
        $this->createResolutions($enemyId, 1);

        // Act + Assert
        $this->artisan('combatlog:analyzeenemyresolutions', [
            'dungeon'           => $this->dungeon->key,
            '--mapping-version' => $this->mappingVersion->id,
            '--format'          => 'json',
            '--hide-low-volume' => true,
        ])
            ->expectsOutputToContain('"min_route_share"')
            ->doesntExpectOutputToContain((string)$enemyId)
            ->assertSuccessful();
    }

    /**
     * $routeCount resolutions of the same enemy, each on its own route, all engaged the same way off its mapped spot.
     */
    private function createResolutions(int $enemyId, int $routeCount): void
    {
        for ($i = 0; $i < $routeCount; $i++) {
            $this->createdResolutionIds[] = CombatLogRouteEnemyResolution::create([
                'dungeon_route_id'   => 8000 + $i,
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'enemy_id'           => $enemyId,
                'lat'                => -100.0,
                'lng'                => 150.0,
                'enemy_lat'          => -101.0,
                'enemy_lng'          => 150.0,
                'distance'           => 60,
                'weighted_distance'  => 60,
            ])->id;
        }
    }
}
