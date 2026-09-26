<?php

namespace Tests\Feature\App\Repository;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellTuningBuild;
use App\Models\Spell\SpellTuningChange;
use App\Repositories\Interfaces\Spell\SpellTuningBuildRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellTuning')]
final class SpellTuningBuildRepositoryTest extends PublicTestCase
{
    private const string OLD_BUILD = '0.0.0.00011';

    private const string MID_BUILD = '0.0.0.00012';

    private const string NEW_BUILD = '0.0.0.00013';

    private const string QUIET_BUILD = '0.0.0.00014';

    private SpellTuningBuildRepositoryInterface $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(SpellTuningBuildRepositoryInterface::class);
    }

    #[Test]
    public function getBuilds_givenBuildsWithAndWithoutChanges_returnsAllNewestFirst(): void
    {
        // Arrange
        /** @var Collection<int, Model> $created */
        $created = new Collection();

        try {
            $spell         = Spell::query()->where('hidden_on_map', false)->orderBy('id')->firstOrFail();
            $gameVersionId = $spell->game_version_id;

            $created->push($this->createBuild($gameVersionId, self::OLD_BUILD, self::MID_BUILD, 12));
            $created->push($this->createBuild($gameVersionId, self::MID_BUILD, self::NEW_BUILD, 13));
            $created->push($this->createBuild($gameVersionId, self::NEW_BUILD, self::QUIET_BUILD, 14));
            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spell->id, 'game_version_id' => $gameVersionId, 'from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12]));

            // Act
            $builds = $this->getTestBuilds($gameVersionId);

            // Assert
            $this->assertSame([self::QUIET_BUILD, self::NEW_BUILD, self::MID_BUILD], array_map(static fn(SpellTuningBuild $build): string => $build->to_build, $builds));
            $this->assertSame(self::NEW_BUILD, $builds[0]->from_build);
            $this->assertSame(self::MID_BUILD, $builds[1]->from_build);
            $this->assertSame(self::OLD_BUILD, $builds[2]->from_build);
        } finally {
            $created->each(static fn(Model $model) => $model->delete());
        }
    }

    #[Test]
    public function getBuilds_givenOtherGameVersion_leavesItsBuildsOut(): void
    {
        // Arrange
        /** @var Collection<int, SpellTuningBuild> $created */
        $created = new Collection();

        try {
            $gameVersionId = Spell::query()->where('hidden_on_map', false)->orderBy('id')->firstOrFail()->game_version_id;
            $otherVersion  = GameVersion::query()->whereKeyNot($gameVersionId)->orderBy('id')->firstOrFail();

            $created->push($this->createBuild($gameVersionId, self::OLD_BUILD, self::MID_BUILD, 12));
            $created->push($this->createBuild($otherVersion->id, self::MID_BUILD, self::NEW_BUILD, 13));

            // Act
            $builds = $this->getTestBuilds($gameVersionId);

            // Assert
            $this->assertSame([self::MID_BUILD], array_map(static fn(SpellTuningBuild $build): string => $build->to_build, $builds));
        } finally {
            $created->each(static fn(SpellTuningBuild $build) => $build->delete());
        }
    }

    #[Test]
    public function getBuilds_givenReleasedAt_returnsItAsCarbonAndNullWhenUnknown(): void
    {
        // Arrange
        /** @var Collection<int, SpellTuningBuild> $created */
        $created = new Collection();

        try {
            $gameVersionId = Spell::query()->where('hidden_on_map', false)->orderBy('id')->firstOrFail()->game_version_id;

            $created->push($this->createBuild($gameVersionId, self::OLD_BUILD, self::MID_BUILD, 12));
            $created->push($this->createBuild($gameVersionId, self::MID_BUILD, self::NEW_BUILD, 13, '2001-02-03 04:05:06'));

            // Act
            $builds = collect($this->getTestBuilds($gameVersionId))->keyBy('to_build');

            // Assert
            $this->assertInstanceOf(Carbon::class, $builds[self::NEW_BUILD]->to_build_released_at);
            $this->assertSame('2001-02-03 04:05:06', $builds[self::NEW_BUILD]->to_build_released_at->toDateTimeString());
            $this->assertNull($builds[self::MID_BUILD]->to_build_released_at);
        } finally {
            $created->each(static fn(SpellTuningBuild $build) => $build->delete());
        }
    }

    #[Test]
    public function findReleasedAt_givenDatedBuild_returnsItsDate(): void
    {
        // Arrange
        $build = $this->createBuild(1, self::OLD_BUILD, self::MID_BUILD, 12, '2001-02-03 04:05:06');

        try {
            // Act
            $releasedAt = $this->repository->findReleasedAt($build->game_version_id, self::MID_BUILD);

            // Assert
            $this->assertSame('2001-02-03 04:05:06', $releasedAt?->toDateTimeString());
        } finally {
            $build->delete();
        }
    }

    #[Test]
    public function findReleasedAt_givenUndatedOrUnknownBuild_returnsNull(): void
    {
        // Arrange
        $build = $this->createBuild(1, self::OLD_BUILD, self::MID_BUILD, 12);

        try {
            // Act
            $undated = $this->repository->findReleasedAt($build->game_version_id, self::MID_BUILD);
            $unknown = $this->repository->findReleasedAt($build->game_version_id, self::NEW_BUILD);

            // Assert
            $this->assertNull($undated);
            $this->assertNull($unknown);
        } finally {
            $build->delete();
        }
    }

    #[Test]
    public function record_givenNewBuild_createsIt(): void
    {
        // Arrange
        $releasedAt = Carbon::createFromFormat('Y-m-d H:i:s', '2001-02-03 04:05:06', 'UTC');
        $build      = null;

        try {
            // Act
            $build = $this->repository->record(1, self::OLD_BUILD, self::MID_BUILD, 12, $releasedAt);

            // Assert
            $stored = SpellTuningBuild::query()->where('game_version_id', 1)->where('to_build', self::MID_BUILD)->sole();
            $this->assertSame($build->id, $stored->id);
            $this->assertSame(self::OLD_BUILD, $stored->from_build);
            $this->assertSame(12, $stored->to_build_number);
            $this->assertSame('2001-02-03 04:05:06', $stored->to_build_released_at?->toDateTimeString());
        } finally {
            $build?->delete();
        }
    }

    #[Test]
    public function record_givenExistingBuildAndNoReleasedAt_updatesItAndKeepsItsDate(): void
    {
        // Arrange
        $existing = $this->createBuild(1, self::OLD_BUILD, self::MID_BUILD, 12, '2001-02-03 04:05:06');

        try {
            // Act
            $this->repository->record(1, self::NEW_BUILD, self::MID_BUILD, 12, null);

            // Assert
            $stored = SpellTuningBuild::query()->where('game_version_id', 1)->where('to_build', self::MID_BUILD)->sole();
            $this->assertSame($existing->id, $stored->id);
            $this->assertSame(self::NEW_BUILD, $stored->from_build);
            $this->assertSame('2001-02-03 04:05:06', $stored->to_build_released_at?->toDateTimeString());
        } finally {
            $existing->delete();
        }
    }

    private function createBuild(int $gameVersionId, string $fromBuild, string $toBuild, int $toBuildNumber, ?string $releasedAt = null): SpellTuningBuild
    {
        return SpellTuningBuild::factory()->create([
            'game_version_id'      => $gameVersionId,
            'from_build'           => $fromBuild,
            'to_build'             => $toBuild,
            'to_build_number'      => $toBuildNumber,
            'to_build_released_at' => $releasedAt,
        ]);
    }

    /**
     * The builds this test created, in the order the repository returned them; seeded builds are left out.
     *
     * @return array<int, SpellTuningBuild>
     */
    private function getTestBuilds(int $gameVersionId): array
    {
        return collect($this->repository->getBuilds($gameVersionId, 50)->items())
            ->filter(static fn(SpellTuningBuild $build): bool => in_array($build->to_build, [self::MID_BUILD, self::NEW_BUILD, self::QUIET_BUILD], true))
            ->values()
            ->all();
    }
}
