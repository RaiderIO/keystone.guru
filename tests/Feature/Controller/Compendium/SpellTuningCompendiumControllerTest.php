<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\Spell\SpellTuningChange;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
#[Group('SpellTuning')]
final class SpellTuningCompendiumControllerTest extends PublicTestCase
{
    private const string FROM_BUILD = '0.0.0.00021';

    private const string TO_BUILD = '0.0.0.00022';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::findOrFail(1));
        Feature::define(NpcCompendium::class, true);
    }

    #[Test]
    public function index_givenFeatureDisabled_returnsNotFound(): void
    {
        // Arrange
        $this->actingAsGuest();
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.tuning.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenNoChanges_rendersEmptyState(): void
    {
        // Arrange
        $this->actingAsGuest();
        $removed = $this->stashAllChanges();

        try {
            // Act
            $response = $this->get(route('compendium.tuning.index'));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.tuning.index.header'));
            $response->assertSeeText(__('view_compendium.tuning.index.empty'));
        } finally {
            $this->restoreChanges($removed);
        }
    }

    #[Test]
    public function index_givenChanges_rendersBuildWithSpellLinkAndDelta(): void
    {
        // Arrange
        $this->actingAsGuest();
        $spell   = $this->findVisibleRetailSpell();
        $created = [];

        try {
            $created[] = SpellTuningChange::factory()->create([
                'spell_id'        => $spell->id,
                'game_version_id' => $spell->game_version_id,
                'from_build'      => self::FROM_BUILD,
                'to_build'        => self::TO_BUILD,
                'to_build_number' => 22,
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
        /** @var Dungeon $dungeon */
        $dungeon      = Dungeon::query()->active()->whereDoesntHave('spells')->firstOrFail();
        $created      = [];
        $spellDungeon = null;

        try {
            $spellDungeon = SpellDungeon::query()->create(['spell_id' => $spellIn->id, 'dungeon_id' => $dungeon->id]);

            $created[] = SpellTuningChange::factory()->create(['spell_id' => $spellIn->id, 'game_version_id' => $spellIn->game_version_id, 'from_build' => self::FROM_BUILD, 'to_build' => self::TO_BUILD, 'to_build_number' => 22]);
            $created[] = SpellTuningChange::factory()->create(['spell_id' => $spellOut->id, 'game_version_id' => $spellOut->game_version_id, 'from_build' => self::FROM_BUILD, 'to_build' => self::TO_BUILD, 'to_build_number' => 22]);

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
            $spellDungeon?->delete();
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
        new SpellTuningChange()->flushCache();

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
        new SpellTuningChange()->flushCache();
    }
}
