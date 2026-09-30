<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\Laratrust\Role;
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[\Override]
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
    public function index_givenRunDataOfASeason_countsItUnderThatSeasonAndCoversItsId(): void
    {
        // Arrange
        $season  = sprintf('season-test-%s', bin2hex(random_bytes(4)));
        $runData = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => sprintf('%s - logged: #40 - run: #40', $season),
            'correlation_id'        => 'test-index',
            'post_body'             => '{"index":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $runData->id;

        // Act
        $response = $this->get(route('admin.tools.combatlog.rundata'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('seasonStats', static fn($seasonStats): bool => (int)$seasonStats->firstWhere('season', $season)?->total === 1);
        $response->assertViewHas('maxId', static fn(int $maxId): bool => $maxId >= $runData->id);
        $response->assertViewHas('minId', static fn(int $minId): bool => $minId > 0 && $minId <= $runData->id);
    }

    #[Test]
    public function pruneBatch_givenNoSeasons_returnsValidationError(): void
    {
        // Arrange

        // Act
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'min_id' => 0,
            'max_id' => 1,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['seasons']);
    }

    #[Test]
    public function pruneBatch_givenNonAdmin_returnsForbiddenWithoutPruning(): void
    {
        // Arrange
        $runData = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-2 - logged: #41 - run: #41',
            'correlation_id'        => 'test-non-admin',
            'post_body'             => '{"non_admin":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $runData->id;
        $user                      = User::factory()->create();

        try {
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $this->be($user);

            // Act
            $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
                'seasons' => ['season-tww-3'],
                'min_id'  => $runData->id,
                'max_id'  => $runData->id,
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertNotEmpty($runData->fresh()->post_body);
        } finally {
            $user->delete();
        }
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

        $minId = min($keepSeason->id, $pruneRow->id, $ptrRow->id);
        $maxId = max($keepSeason->id, $pruneRow->id, $ptrRow->id);

        // Act — keep season-tww-3 and season-tww-3-ptr, prune season-tww-2
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3', 'season-tww-3-ptr'],
            'min_id'  => $minId,
            'max_id'  => $maxId,
        ]);

        // Assert
        $response->assertOk()->assertExactJson(['pruned' => 1]);
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

        $minId = min($parentRow->id, $ptrRow->id);
        $maxId = max($parentRow->id, $ptrRow->id);

        // Act — only keep season-tww-3, NOT season-tww-3-ptr
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3'],
            'min_id'  => $minId,
            'max_id'  => $maxId,
        ]);

        // Assert
        $response->assertOk()->assertExactJson(['pruned' => 1]);
        $this->assertNotEmpty($parentRow->fresh()->post_body);
        $this->assertEmpty($ptrRow->fresh()->post_body);
    }

    #[Test]
    public function pruneBatch_givenIdRange_onlyPrunesRowsWithinRange(): void
    {
        // Arrange
        $inRangeRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-2 - logged: #20 - run: #20',
            'correlation_id'        => 'test-in-range',
            'post_body'             => '{"in_range":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $inRangeRow->id;

        $outOfRangeRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-2 - logged: #21 - run: #21',
            'correlation_id'        => 'test-out-of-range',
            'post_body'             => '{"out_of_range":true}',
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $outOfRangeRow->id;

        // Act — only cover the first row's ID range
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3'],
            'min_id'  => $inRangeRow->id,
            'max_id'  => $inRangeRow->id,
        ]);

        // Assert
        $response->assertOk()->assertExactJson(['pruned' => 1]);
        $this->assertEmpty($inRangeRow->fresh()->post_body);
        $this->assertNotEmpty($outOfRangeRow->fresh()->post_body);
    }

    #[Test]
    public function pruneBatch_givenNullPostBody_prunesNullRows(): void
    {
        // Arrange — NULL post_body rows must be pruned (set to '') just like non-empty rows
        $nullBodyRow = ChallengeModeRunData::forceCreate([
            'challenge_mode_run_id' => 0,
            'run_id'                => 'season-tww-2 - logged: #30 - run: #30',
            'correlation_id'        => 'test-null-body',
            'post_body'             => null,
            'processed'             => 0,
        ]);
        $this->createdRunDataIds[] = $nullBodyRow->id;

        // Act
        $response = $this->postJson(route('admin.tools.combatlog.rundata.prune_batch'), [
            'seasons' => ['season-tww-3'],
            'min_id'  => $nullBodyRow->id,
            'max_id'  => $nullBodyRow->id,
        ]);

        // Assert
        $response->assertOk()->assertJsonStructure(['pruned']);
        $this->assertSame(null, $nullBodyRow->fresh()->post_body);
    }
}
