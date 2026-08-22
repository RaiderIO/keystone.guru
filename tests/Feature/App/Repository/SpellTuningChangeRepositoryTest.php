<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\Spell\SpellTuningChange;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellTuning')]
final class SpellTuningChangeRepositoryTest extends PublicTestCase
{
    private const string OLD_BUILD = '0.0.0.00011';

    private const string MID_BUILD = '0.0.0.00012';

    private const string NEW_BUILD = '0.0.0.00013';

    private SpellTuningChangeRepositoryInterface $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(SpellTuningChangeRepositoryInterface::class);
    }

    #[Test]
    public function getForSpell_givenChangesAcrossBuilds_returnsNewestBuildFirst(): void
    {
        // Arrange
        /** @var Collection<int, SpellTuningChange> $created */
        $created = new Collection();

        try {
            $created->push(SpellTuningChange::factory()->create(['from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12, 'value_index' => 1]));
            $created->push(SpellTuningChange::factory()->create(['from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12, 'value_index' => 0]));
            $created->push(SpellTuningChange::factory()->create(['from_build' => self::MID_BUILD, 'to_build' => self::NEW_BUILD, 'to_build_number' => 13, 'value_index' => 0]));
            $spellId = $created->firstOrFail()->spell_id;

            // Act
            $changes = $this->repository->getForSpell($spellId)->filter(
                static fn(SpellTuningChange $change): bool => $created->contains('id', $change->id),
            )->values();

            // Assert
            $this->assertCount(3, $changes);
            $this->assertSame([self::NEW_BUILD, self::MID_BUILD, self::MID_BUILD], $changes->pluck('to_build')->all());
            $this->assertSame([0, 0, 1], $changes->pluck('value_index')->all());
        } finally {
            $created->each(static fn(SpellTuningChange $change) => $change->delete());
        }
    }

    #[Test]
    public function getBuilds_givenChanges_returnsBuildsNewestFirstWithSpellCounts(): void
    {
        // Arrange
        /** @var Collection<int, SpellTuningChange> $created */
        $created = new Collection();

        try {
            [$spellA, $spellB] = Spell::query()->where('hidden_on_map', false)->orderBy('id')->limit(2)->get()->all();
            $gameVersionId     = $spellA->game_version_id;

            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellA->id, 'game_version_id' => $gameVersionId, 'from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12]));
            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellA->id, 'game_version_id' => $gameVersionId, 'from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12, 'value_index' => 1]));
            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellB->id, 'game_version_id' => $gameVersionId, 'from_build' => self::OLD_BUILD, 'to_build' => self::MID_BUILD, 'to_build_number' => 12]));
            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellA->id, 'game_version_id' => $gameVersionId, 'from_build' => self::MID_BUILD, 'to_build' => self::NEW_BUILD, 'to_build_number' => 13]));

            // Act
            $builds = collect($this->repository->getBuilds($gameVersionId, null, 50)->items())
                ->filter(static fn(array $build): bool => in_array($build['to_build'], [self::MID_BUILD, self::NEW_BUILD], true))
                ->values();

            // Assert
            $this->assertCount(2, $builds);
            $this->assertSame(self::NEW_BUILD, $builds[0]['to_build']);
            $this->assertSame(self::MID_BUILD, $builds[0]['from_build']);
            $this->assertSame(1, $builds[0]['spell_count']);
            $this->assertSame(self::MID_BUILD, $builds[1]['to_build']);
            $this->assertSame(2, $builds[1]['spell_count']);
        } finally {
            $created->each(static fn(SpellTuningChange $change) => $change->delete());
        }
    }

    #[Test]
    public function getForBuild_givenDungeon_returnsOnlySpellsOfThatDungeon(): void
    {
        // Arrange
        $created      = new Collection();
        $spellDungeon = null;

        try {
            [$spellIn, $spellOut] = Spell::query()->where('hidden_on_map', false)->orderBy('id')->limit(2)->get()->all();
            $gameVersionId        = $spellIn->game_version_id;
            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::query()->whereDoesntHave('spells')->firstOrFail();

            $spellDungeon = SpellDungeon::query()->create(['spell_id' => $spellIn->id, 'dungeon_id' => $dungeon->id]);

            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellIn->id, 'game_version_id' => $gameVersionId, 'to_build' => self::NEW_BUILD, 'to_build_number' => 13, 'delta' => 0.1]));
            $created->push(SpellTuningChange::factory()->create(['spell_id' => $spellOut->id, 'game_version_id' => $gameVersionId, 'to_build' => self::NEW_BUILD, 'to_build_number' => 13, 'delta' => 0.5]));

            // Act
            $scoped = $this->repository->getForBuild($gameVersionId, self::NEW_BUILD, $dungeon);
            $all    = $this->repository->getForBuild($gameVersionId, self::NEW_BUILD, null);

            // Assert
            $this->assertSame([$spellIn->id], $scoped->pluck('spell_id')->all());
            $this->assertTrue($scoped->first()->relationLoaded('spell'));
            // Unscoped: biggest swing first
            $this->assertSame([$spellOut->id, $spellIn->id], $all->pluck('spell_id')->all());
        } finally {
            $created->each(static fn(SpellTuningChange $change) => $change->delete());
            $spellDungeon?->delete();
        }
    }

    #[Test]
    public function replaceForBuild_givenExistingRows_replacesOnlyThatBuild(): void
    {
        // Arrange
        /** @var Collection<int, SpellTuningChange> $created */
        $created = new Collection();

        try {
            $other = SpellTuningChange::factory()->create(['to_build' => self::MID_BUILD, 'to_build_number' => 12]);
            $created->push($other);
            $stale              = SpellTuningChange::factory()->create(['to_build' => self::NEW_BUILD, 'to_build_number' => 13]);
            $row                = $stale->only(['game_version_id', 'spell_id', 'from_build', 'to_build', 'to_build_number', 'value_index', 'old_coefficient', 'new_coefficient', 'old_text', 'new_text', 'delta']);
            $row['change_type'] = $stale->change_type->value;
            $row['kind']        = $stale->kind->value;
            $row['new_text']    = 'replaced';

            // Act
            $inserted = $this->repository->replaceForBuild($stale->game_version_id, self::NEW_BUILD, [$row, $row]);

            // Assert
            $this->assertSame(2, $inserted);
            $remaining = SpellTuningChange::query()->where('to_build', self::NEW_BUILD)->get();
            $this->assertCount(2, $remaining);
            $this->assertSame(['replaced', 'replaced'], $remaining->pluck('new_text')->all());
            $this->assertNotNull(SpellTuningChange::query()->find($other->id), 'Rows of other builds must survive');
            $created->push(...$remaining->all());
        } finally {
            $created->each(static fn(SpellTuningChange $change) => $change->delete());
        }
    }
}
