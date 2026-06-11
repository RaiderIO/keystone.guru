<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
#[SlowTest]
final class AdminToolsCombatLogRunDataControllerTest extends PublicTestCase
{
    /** @var array<int> */
    private array $createdRunDataIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        try {
            ChallengeModeRunData::query()->whereIn('id', $this->createdRunDataIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.combatlog.rundata'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function prune_givenSelectedSeasons_nullsPostBodyForOtherSeasons(): void
    {
        // Arrange
        $keepSeason = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-3 - logged: #1 - run: #1',
            'correlation_id'        => 'test-keep',
            'post_body'             => '{"keep":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $keepSeason->id;

        $pruneRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-2 - logged: #2 - run: #2',
            'correlation_id'        => 'test-prune',
            'post_body'             => '{"prune":true}',
            'processed'             => 1,
        ]);
        $this->createdRunDataIds[] = $pruneRow->id;

        // season-tww-3-ptr shares a prefix with season-tww-3 — must NOT be pruned when season-tww-3-ptr is kept
        $ptrRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-3-ptr - logged: #3 - run: #3',
            'correlation_id'        => 'test-ptr-keep',
            'post_body'             => '{"ptr":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $ptrRow->id;

        // Act — keep season-tww-3 and season-tww-3-ptr, prune season-tww-2
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3', 'season-tww-3-ptr'],
        ]);

        // Assert
        $response->assertOk()->assertJsonStructure(['pruned', 'remaining']);
        $this->assertNotEmpty($keepSeason->fresh()->post_body);
        $this->assertNotEmpty($ptrRow->fresh()->post_body);
        $this->assertEmpty($pruneRow->fresh()->post_body);
    }

    #[Test]
    public function prune_givenPrefixCollision_doesNotPruneSubseasonWhenOnlyParentKept(): void
    {
        // Arrange — season-tww-3-ptr must NOT survive if only season-tww-3 is kept
        $parentRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-3 - logged: #10 - run: #10',
            'correlation_id'        => 'test-parent',
            'post_body'             => '{"parent":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $parentRow->id;

        $ptrRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-3-ptr - logged: #11 - run: #11',
            'correlation_id'        => 'test-ptr-prune',
            'post_body'             => '{"ptr":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $ptrRow->id;

        // Act — only keep season-tww-3, NOT season-tww-3-ptr
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3'],
        ]);

        // Assert
        $response->assertOk()->assertJsonStructure(['pruned', 'remaining']);
        $this->assertNotEmpty($parentRow->fresh()->post_body);
        $this->assertEmpty($ptrRow->fresh()->post_body);
    }
}
