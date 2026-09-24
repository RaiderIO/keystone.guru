<?php

namespace Tests\Feature\Controller\Compendium;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\Spell\SpellTuningBuild;
use App\Models\Spell\SpellTuningChange;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
#[Group('SpellTuning')]
final class SpellTuningCompendiumControllerTest extends PublicTestCase
{
    use CreatesDungeon;

    private const string FROM_BUILD = '0.0.0.999921';

    // Above every seeded build, so the test build is on the first page
    private const string TO_BUILD = '0.0.0.999922';

    private const int TO_BUILD_NUMBER = 999922;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
    }

    #[Test]
    public function index_givenNoChanges_rendersEmptyState(): void
    {
        // Arrange
        $this->actingAsGuest();
        $removed       = $this->stashAllChanges();
        $removedBuilds = $this->stashAllBuilds();

        try {
            // Act
            $response = $this->get(route('compendium.tuning.index'));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.tuning.index.header'));
            $response->assertSeeText(__('view_compendium.tuning.index.empty'));
        } finally {
            $this->restoreChanges($removed);
            SpellTuningBuild::query()->insert($removedBuilds);
        }
    }

    #[Test]
    public function index_givenChanges_rendersBuildWithSpellLinkAndDelta(): void
    {
        // Arrange
        $this->actingAsGuest();
        $spell   = $this->findVisibleRetailSpell();
        $created = [];
        $build   = null;

        try {
            $build     = $this->createBuild();
            $created[] = SpellTuningChange::factory()->create([
                'spell_id'        => $spell->id,
                'game_version_id' => $spell->game_version_id,
                'from_build'      => self::FROM_BUILD,
                'to_build'        => self::TO_BUILD,
                'to_build_number' => self::TO_BUILD_NUMBER,
                'old_text'        => '11,111',
                'new_text'        => '16,667',
                'delta'           => 0.5,
            ]);

            // Act
            $response = $this->get(route('compendium.tuning.index'));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.tuning.index.build_title', ['build' => self::TO_BUILD]));
            $response->assertSeeText(__('view_compendium.tuning.index.build_subtitle', ['from' => self::FROM_BUILD]));
            $response->assertSeeText(trans_choice('view_compendium.tuning.index.changed_spells', 1, ['count' => 1]));
            $response->assertSee(route('spell.compendium.show', $spell), false);
            $response->assertSeeTextInOrder(['11,111', '16,667', '+50%']);
        } finally {
            foreach ($created as $change) {
                $change->delete();
            }
            $build?->delete();
        }
    }

    #[Test]
    public function index_givenBuildWithReleasedAt_rendersTheDateItWentLive(): void
    {
        // Arrange
        $this->actingAsGuest();
        $spell   = $this->findVisibleRetailSpell();
        $created = [];
        $build   = null;

        try {
            $build     = $this->createBuild('2001-02-03 04:05:06');
            $created[] = SpellTuningChange::factory()->create([
                'spell_id'             => $spell->id,
                'game_version_id'      => $spell->game_version_id,
                'from_build'           => self::FROM_BUILD,
                'to_build'             => self::TO_BUILD,
                'to_build_number'      => self::TO_BUILD_NUMBER,
                'to_build_released_at' => '2001-02-03 04:05:06',
            ]);

            // Act
            $response = $this->get(route('compendium.tuning.index'));

            // Assert
            $response->assertOk();
            $response->assertSeeTextInOrder([
                __('view_compendium.tuning.index.build_title', ['build' => self::TO_BUILD]),
                __('view_compendium.sections.tuning_build_released_at.went_live', ['date' => 'Feb 3, 2001']),
                __('view_compendium.tuning.index.build_subtitle', ['from' => self::FROM_BUILD]),
            ]);
            $response->assertSee('<time datetime="2001-02-03T04:05:06Z">', false);
        } finally {
            foreach ($created as $change) {
                $change->delete();
            }
            $build?->delete();
        }
    }

    #[Test]
    public function indexDungeon_givenBuildWithoutReleasedAt_rendersNoDate(): void
    {
        // Arrange
        $this->actingAsGuest();
        $spell        = $this->findVisibleRetailSpell();
        $dungeon      = $this->createDungeon(['active' => true]);
        $created      = [];
        $spellDungeon = null;
        $build        = null;

        try {
            $build        = $this->createBuild();
            $spellDungeon = SpellDungeon::query()->create(['spell_id' => $spell->id, 'dungeon_id' => $dungeon->id]);
            $created[]    = SpellTuningChange::factory()->create([
                'spell_id'             => $spell->id,
                'game_version_id'      => $spell->game_version_id,
                'from_build'           => self::FROM_BUILD,
                'to_build'             => self::TO_BUILD,
                'to_build_number'      => self::TO_BUILD_NUMBER,
                'to_build_released_at' => null,
            ]);

            // Act
            $response = $this->get(route('compendium.tuning', ['dungeon' => $dungeon]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.tuning.index.build_subtitle', ['from' => self::FROM_BUILD]));
            // Seeded builds carry a date, so only this build's section is checked
            $this->assertStringNotContainsString('<time datetime=', $this->getBuildSection((string)$response->getContent(), self::TO_BUILD));
        } finally {
            foreach ($created as $change) {
                $change->delete();
            }
            $build?->delete();
            $spellDungeon?->delete();
        }
    }

    #[Test]
    public function indexDungeon_givenDungeon_showsOnlySpellsOfThatDungeonAndSetsContext(): void
    {
        // Arrange
        $this->actingAsGuest();
        [$spellIn, $spellOut] = Spell::query()
            ->where('hidden_on_map', false)
            ->where('game_version_id', GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();
        $dungeon      = $this->createDungeon(['active' => true]);
        $created      = [];
        $spellDungeon = null;
        $build        = null;

        try {
            $build        = $this->createBuild();
            $spellDungeon = SpellDungeon::query()->create(['spell_id' => $spellIn->id, 'dungeon_id' => $dungeon->id]);

            $created[] = SpellTuningChange::factory()->create(['spell_id' => $spellIn->id, 'game_version_id' => $spellIn->game_version_id, 'from_build' => self::FROM_BUILD, 'to_build' => self::TO_BUILD, 'to_build_number' => self::TO_BUILD_NUMBER]);
            $created[] = SpellTuningChange::factory()->create(['spell_id' => $spellOut->id, 'game_version_id' => $spellOut->game_version_id, 'from_build' => self::FROM_BUILD, 'to_build' => self::TO_BUILD, 'to_build_number' => self::TO_BUILD_NUMBER]);

            // Act
            $response = $this->get(route('compendium.tuning', ['dungeon' => $dungeon]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.tuning.index.header_dungeon', ['dungeon' => __($dungeon->name)]));
            $response->assertSee(route('spell.compendium.show', $spellIn), false);
            $response->assertDontSee(route('spell.compendium.show', $spellOut), false);
            $response->assertSee(route('compendium.tuning.index'), false);
        } finally {
            foreach ($created as $change) {
                $change->delete();
            }
            $build?->delete();
            $spellDungeon?->delete();
        }
    }

    #[Test]
    public function index_givenBuildWithoutChanges_rendersItWithNoChangesMessage(): void
    {
        // Arrange
        $this->actingAsGuest();
        $build = $this->createBuild();

        try {
            // Act
            $response = $this->get(route('compendium.tuning.index'));

            // Assert
            $response->assertOk();
            $section = $this->getBuildSection((string)$response->getContent(), self::TO_BUILD);
            $this->assertStringContainsString(e(__('view_compendium.tuning.index.build_subtitle', ['from' => self::FROM_BUILD])), $section);
            $this->assertStringContainsString(e(trans_choice('view_compendium.tuning.index.changed_spells', 0, ['count' => 0])), $section);
            $this->assertStringContainsString(e(__('view_compendium.tuning.index.build_no_changes')), $section);
        } finally {
            $build->delete();
        }
    }

    #[Test]
    public function indexDungeon_givenBuildWithChangesOnlyInOtherDungeons_rendersItWithDungeonNoChangesMessage(): void
    {
        // Arrange
        $this->actingAsGuest();
        $spell   = $this->findVisibleRetailSpell();
        $dungeon = $this->createDungeon(['active' => true]);
        $change  = null;
        $build   = null;

        try {
            $build  = $this->createBuild();
            $change = SpellTuningChange::factory()->create([
                'spell_id'        => $spell->id,
                'game_version_id' => $spell->game_version_id,
                'from_build'      => self::FROM_BUILD,
                'to_build'        => self::TO_BUILD,
                'to_build_number' => self::TO_BUILD_NUMBER,
            ]);

            // Act
            $response = $this->get(route('compendium.tuning', ['dungeon' => $dungeon]));

            // Assert
            $response->assertOk();
            $section = $this->getBuildSection((string)$response->getContent(), self::TO_BUILD);
            $this->assertStringContainsString(e(trans_choice('view_compendium.tuning.index.changed_spells', 0, ['count' => 0])), $section);
            $this->assertStringContainsString(e(__('view_compendium.tuning.index.build_no_changes_dungeon')), $section);
            $this->assertStringNotContainsString(route('spell.compendium.show', $spell), $section);
        } finally {
            $change?->delete();
            $build?->delete();
        }
    }

    #[Test]
    public function indexDungeon_givenUnknownDungeon_returnsNotFound(): void
    {
        // Act
        $response = $this->get('/compendium/dungeon/not-a-dungeon/tuning');

        // Assert
        $response->assertNotFound();
    }

    private function createBuild(?string $releasedAt = null): SpellTuningBuild
    {
        return SpellTuningBuild::factory()->create([
            'game_version_id'      => GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id,
            'from_build'           => self::FROM_BUILD,
            'to_build'             => self::TO_BUILD,
            'to_build_number'      => self::TO_BUILD_NUMBER,
            'to_build_released_at' => $releasedAt,
        ]);
    }

    /**
     * The page's markup from the given build's heading up to the next build's heading.
     */
    private function getBuildSection(string $html, string $build): string
    {
        $start = strpos($html, e(__('view_compendium.tuning.index.build_title', ['build' => $build])));
        $this->assertNotFalse($start, sprintf('Build %s is not on the page', $build));

        $end = strpos($html, 'compendium_tuning_build_heading', $start);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    /**
     * Moves every seeded build out of the table so the empty state can be observed, returning what was
     * removed so it can be put back.
     *
     * @return array<int, array<string, mixed>>
     */
    private function stashAllBuilds(): array
    {
        $rows = SpellTuningBuild::query()->get()->makeHidden(['id'])->toArray();
        SpellTuningBuild::query()->delete();

        return $rows;
    }

    private function findVisibleRetailSpell(): Spell
    {
        return Spell::query()
            ->where('hidden_on_map', false)
            ->where('game_version_id', GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL)->id)
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * Moves every seeded change out of the table so the empty state can be observed, returning what was
     * removed so it can be put back.
     *
     * @return array<int, array<string, mixed>>
     */
    private function stashAllChanges(): array
    {
        $rows = SpellTuningChange::query()->get()->makeHidden(['id'])->toArray();
        SpellTuningChange::query()->delete();

        return array_map(static function (array $row): array {
            // Enum casts serialize to their values already; nothing else to convert
            return $row;
        }, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function restoreChanges(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            SpellTuningChange::query()->insert($chunk);
        }
    }
}
