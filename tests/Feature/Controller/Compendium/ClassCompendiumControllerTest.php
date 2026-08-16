<?php

namespace Tests\Feature\Controller\Compendium;

use App\Features\NpcCompendium;
use App\Models\CharacterClass;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Compendium')]
final class ClassCompendiumControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    /** @var array<int, array{0: int, 1: int}> npc_id/characteristic_id pairs this test created */
    private array $createdNpcCharacteristics = [];

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
        Feature::define(NpcCompendium::class, false);

        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_givenFeatureEnabled_returnsOk(): void
    {
        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function index_givenFeatureEnabled_displaysAllClasses(): void
    {
        // Act
        $response = $this->get(route('compendium.class.index'));

        // Assert
        $response->assertOk();
        foreach (CharacterClass::all() as $characterClass) {
            $response->assertSeeText(__($characterClass->name));
        }
    }

    #[Test]
    public function show_givenValidClass_returnsOk(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function show_givenNoDungeonInUrl_redirectsToContextDungeon(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();
        $dungeon        = Dungeon::active()->firstOrFail();

        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();

        try {
            // Act
            $response = $this->actingAs($user->fresh())->get(route('compendium.class.show', $characterClass));

            // Assert - a 302, not a 301: the target depends on the visitor's own context dungeon
            $response->assertRedirect(route('compendium.class.show.dungeon', [
                'characterClass' => $characterClass,
                'dungeon'        => $dungeon,
            ]));
            $response->assertStatus(302);
        } finally {
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function showDungeon_givenDungeonOtherThanContextDungeon_rendersThatDungeonAndMakesItTheContext(): void
    {
        // Arrange - two different dungeons, so the URL is provably what decides what is rendered
        $characterClass   = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();
        $contextDungeon   = Dungeon::active()->orderBy('id')->firstOrFail();
        $requestedDungeon = Dungeon::active()->where('id', '!=', $contextDungeon->id)->orderBy('id')->firstOrFail();

        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $contextDungeon->id;
        $user->save();

        try {
            // Act
            $response = $this->actingAs($user->fresh())->get(route('compendium.class.show.dungeon', [
                'characterClass' => $characterClass,
                'dungeon'        => $requestedDungeon,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__($requestedDungeon->name));
            $this->assertSame($requestedDungeon->id, User::findOrFail(1)->dungeon_id);
        } finally {
            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function showDungeon_givenUnknownDungeonSlug_returnsNotFound(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();

        // Act
        $response = $this->get(sprintf('/compendium/dungeon/not-a-dungeon/class/%s', $characterClass->key));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function show_givenInvalidClass_returnsNotFound(): void
    {
        // Act
        $response = $this->get(route('compendium.class.show', ['characterClass' => 'invalid_class']));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function show_givenValidClass_displaysClassNameAndContextDungeon(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_MAGE)->firstOrFail();
        $dungeon        = Dungeon::getUserOrDefaultDungeon();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__($characterClass->name));
        $response->assertSeeText(__($dungeon->name));
    }

    #[Test]
    public function show_givenRogueClass_displaysVanishAndShadowmeldCounterSections(): void
    {
        // Arrange — Rogue has Vanish as a class ability, and can be a Night Elf (Shadowmeld racial)
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_ROGUE)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('spellcounters.vanish'));
        $response->assertSeeText(__('spellcounters.shadowmeld'));
    }

    #[Test]
    public function show_givenPaladinClass_doesNotDisplayVanishOrShadowmeldCounterSections(): void
    {
        // Arrange — Vanish is Rogue-only, so Paladin must not see it. Paladin also cannot be a Night
        // Elf, so the Shadowmeld racial counter must not appear either.
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_PALADIN)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSeeText(__('spellcounters.vanish'));
        $response->assertDontSeeText(__('spellcounters.shadowmeld'));
    }

    #[Test]
    public function show_givenWarriorClass_displaysReflectSection(): void
    {
        // Arrange - Spell Reflection is the only reflect ability in retail Mythic+
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertSeeText(__('view_compendium.class.show.reflect.title'));
    }

    #[Test]
    public function show_givenPaladinClass_doesNotDisplayReflectSection(): void
    {
        // Arrange - Paladin has no reflect ability, so the section must not be rendered at all
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_PALADIN)->firstOrFail();

        // Act
        $response = $this->get(route('compendium.class.show.dungeon', [
            'characterClass' => $characterClass,
            'dungeon'        => Dungeon::getUserOrDefaultDungeon(),
        ]));

        // Assert
        $response->assertOk();
        $response->assertDontSeeText(__('view_compendium.class.show.reflect.title'));
    }

    #[Test]
    public function show_givenReflectedSpellInDungeon_displaysSpellAndCastingNpc(): void
    {
        // Arrange
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_WARRIOR)->firstOrFail();

        // Find a Retail dungeon whose current mapping version has enemies AND an NPC without any
        // characteristic or spell of its own, so the NPC cannot also be listed by the CC table or a
        // counter section and make the assertion below pass for the wrong reason. The NPC requirement
        // has to be part of the dungeon selection rather than a follow-up query: an MDT mapping import
        // can give every NPC in a given dungeon a spell (MDT 6.2.1 did exactly that to the Midnight
        // dungeons, see #3980), which leaves that dungeon with no candidate at all.
        $defaultGameVersion               = GameVersion::getDefaultGameVersion();
        [$dungeon, $mappingVersion, $npc] = $this->findDungeon(
            challengeMode: true,
            minEnemies:    1,
            gameVersion:   $defaultGameVersion,
            shuffle:       false,
            constraint:    static fn(Builder $query) => $query
                ->where('challenge_mode_id', '>', 0)
                ->orderByDesc('dungeons.id'),
            resolve:       static fn(Dungeon $dungeon, MappingVersion $mappingVersion) => Npc::query()
                ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
                ->where('enemies.mapping_version_id', $mappingVersion->id)
                ->whereNotIn('npcs.id', static function ($sub): void {
                    $sub->select('npc_id')->from('npc_characteristics');
                })
                ->whereNotIn('npcs.id', static function ($sub): void {
                    $sub->select('npc_id')->from('npc_spells');
                })
                ->select('npcs.*')
                ->first(),
        );
        $this->assertNotNull($npc);

        // Deliberately without a characteristic or counter bit, so the spell cannot also show up in
        // the CC table or a counter section and make the assertions below pass for the wrong reason
        $spell = Spell::query()
            ->where('game_version_id', $mappingVersion->game_version_id)
            ->where('hidden_on_map', false)
            ->whereNull('characteristic_id')
            ->where('counters_mask', 0)
            ->whereRaw('miss_types_mask & ? = 0', [Spell::MISS_TYPE_REFLECT])
            ->first();
        $this->assertNotNull($spell);
        $originalMissTypesMask = $spell->miss_types_mask;

        // Set the user's context dungeon to the one with enemies
        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();
        $user = $user->fresh();

        $spellDungeon = null;
        $npcSpell     = null;

        try {
            $spell->miss_types_mask = $originalMissTypesMask | Spell::MISS_TYPE_REFLECT;
            $spell->save();

            $spellDungeon = SpellDungeon::create([
                'spell_id'   => $spell->id,
                'dungeon_id' => $dungeon->id,
            ]);
            $npcSpell = NpcSpell::create([
                'npc_id'   => $npc->id,
                'spell_id' => $spell->id,
            ]);

            // Act
            $response = $this->actingAs($user)->get(route('compendium.class.show.dungeon', [
                'characterClass' => $characterClass,
                'dungeon'        => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.reflect.title'));
            $response->assertSeeText(__($spell->name));
            $response->assertSeeText(__($npc->name));
        } finally {
            $npcSpell?->delete();
            $spellDungeon?->delete();

            $spell->miss_types_mask = $originalMissTypesMask;
            $spell->save();

            $user->dungeon_id = $originalDungeonId;
            $user->save();
        }
    }

    #[Test]
    public function showDungeon_givenAffectedElite_omitsItAsExpectedBehaviour(): void
    {
        // Arrange - on the Paladin scenario rather than a live class/dungeon pair. Picking an
        // arbitrary NPC out of a real dungeon made this pass for the wrong reason: an elite one would
        // enter the unaffected universe and get listed under "No effect observed" on the class's other
        // rows, and Mage additionally renders NPC names in its counter section, so assertDontSeeText
        // could not tell "the row omitted it" from "nothing put it there yet".
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // The elite was observed affected, which is exactly what a player already assumes for
            // trash - so the row reports the absence of surprises rather than the NPC
            $this->assignCharacteristics([
                $scenario['elites'][0]->id => $scenario['characteristicIds'][0],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $response->assertDontSeeText(__($scenario['elites'][0]->name));

            $observedSpell = $scenario['spells']->firstWhere('characteristic_id', $scenario['characteristicIds'][0]);
            $this->assertNotNull($observedSpell);

            $row = $this->getRowHtmlForSpell((string)$response->getContent(), $observedSpell);
            $this->assertStringContainsString(__('view_compendium.class.show.npcs_no_exceptions'), $row);
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenEliteObservedForAnotherCharacteristic_displaysItUnderNoEffect(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // Both elites enter the universe, but each was only ever observed for one of the two
            // characteristics - so on the other's row it is the surprise worth reporting
            $this->assignCharacteristics([
                $scenario['elites'][0]->id => $scenario['characteristicIds'][0],
                $scenario['elites'][1]->id => $scenario['characteristicIds'][1],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.npcs_no_effect'));
            $response->assertSeeText(__($scenario['elites'][0]->name));
            $response->assertSeeText(__($scenario['elites'][1]->name));
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenNormalNpcObservedForAnotherCharacteristic_displaysItUnderNoEffect(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // A normal-classification trash mob is exactly as "everyone assumes it's affected by
            // everything" as an elite or a rare, so a real immunity there is just as surprising and
            // worth reporting - normal NPCs are no longer excluded from the universe
            $this->assignCharacteristics([
                $scenario['elites'][0]->id => $scenario['characteristicIds'][0],
                $scenario['normal']->id    => $scenario['characteristicIds'][1],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.npcs_no_effect'));
            $response->assertSeeText(__($scenario['elites'][0]->name));
            $response->assertSeeText(__($scenario['normal']->name));
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenBossObservedAffected_displaysItUnderWorksOn(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // A boss that can be crowd controlled at all is the other half of the surprise this page
            // reports - and unlike the unaffected side it rests on a positive observation
            $this->assignCharacteristics([
                $scenario['boss']->id => $scenario['characteristicIds'][0],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.npcs_works_on'));
            $response->assertSeeText(__($scenario['boss']->name));
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenBossObservedForOneCharacteristic_omitsItFromNoEffectOnTheOthers(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // Were bosses allowed into the unaffected universe, this single observation ("this boss was
            // stunned") would silently become a claim on every other row ("...so it resists fear")
            $this->assignCharacteristics([
                $scenario['boss']->id      => $scenario['characteristicIds'][0],
                $scenario['elites'][0]->id => $scenario['characteristicIds'][1],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $response->assertSeeText(__('view_compendium.class.show.npcs_works_on'));

            // Counting the link rather than the name: the boss legitimately appears once, under
            // worksOn, so only a second occurrence proves it leaked into the unaffected universe
            $bossLink = sprintf('href="%s"', route('npc.compendium.show', $scenario['boss']));
            $this->assertSame(1, substr_count((string)$response->getContent(), $bossLink));
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenNpcObservedOnlyForTaunt_isEligibleForNoEffectOnOtherCharacteristics(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // elites[0] is observed for a real characteristic and never for taunt, so it must surface
            // as unaffected on taunt's own row. elites[2] is only ever observed being taunted - taunt
            // must still admit it into the universe, so it is eligible to be reported as unaffected on
            // another characteristic's row, even though the taunt observation itself proves nothing
            // about that other characteristic. elites[1] is never observed for anything, so it must
            // stay out of every row.
            $this->assignCharacteristics([
                $scenario['elites'][0]->id => $scenario['characteristicIds'][0],
                $scenario['elites'][2]->id => $scenario['tauntCharacteristicId'],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert
            $response->assertOk();
            $content = (string)$response->getContent();

            $observedSpell = $scenario['spells']->firstWhere('characteristic_id', $scenario['characteristicIds'][0]);
            $this->assertNotNull($observedSpell);
            $observedCharacteristicRow = $this->getRowHtmlForSpell($content, $observedSpell);
            $this->assertStringContainsString(__('view_compendium.class.show.npcs_no_effect'), $observedCharacteristicRow);
            $this->assertStringContainsString(__($scenario['elites'][2]->name), $observedCharacteristicRow);
            $this->assertStringNotContainsString(__($scenario['elites'][1]->name), $observedCharacteristicRow);

            $tauntSpell = $scenario['spells']->firstWhere('characteristic_id', $scenario['tauntCharacteristicId']);
            $this->assertNotNull($tauntSpell);
            $tauntRow = $this->getRowHtmlForSpell($content, $tauntSpell);
            $this->assertStringContainsString(__('view_compendium.class.show.npcs_no_effect'), $tauntRow);
            $this->assertStringContainsString(__($scenario['elites'][0]->name), $tauntRow);
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenCharacteristicNeverObserved_displaysNoData(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // Only the first characteristic gets an observation - the second has never been seen landing
            // on anything, which says nothing about the NPCs it did not land on
            $this->assignCharacteristics([
                $scenario['elites'][0]->id => $scenario['characteristicIds'][0],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert - scoped to the second characteristic's own row. Several other rows of the class
            // legitimately render "No data" too, so a page-level assertion would pass even with the
            // arrangement above deleted
            $response->assertOk();

            $unobservedSpell = $scenario['spells']->firstWhere('characteristic_id', $scenario['characteristicIds'][1]);
            $this->assertNotNull($unobservedSpell);

            $row = $this->getRowHtmlForSpell((string)$response->getContent(), $unobservedSpell);
            $this->assertStringContainsString(__('view_compendium.class.show.npcs_no_data'), $row);
            $this->assertStringNotContainsString(__('view_compendium.class.show.npcs_no_exceptions'), $row);
        } finally {
            $scenario['cleanUp']();
        }
    }

    #[Test]
    public function showDungeon_givenOnlyNormalTrashObserved_displaysNothingUnexpected(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            // The characteristic was observed landing, and everything it landed on behaved exactly as a
            // player would assume - which must read differently from having no observations at all
            $this->assignCharacteristics([
                $scenario['normal']->id => $scenario['characteristicIds'][0],
            ]);

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert - on the observed characteristic's own row, since "No data" rows elsewhere must
            // not be mistaken for this state
            $response->assertOk();

            $observedSpell = $scenario['spells']->firstWhere('characteristic_id', $scenario['characteristicIds'][0]);
            $this->assertNotNull($observedSpell);

            $row = $this->getRowHtmlForSpell((string)$response->getContent(), $observedSpell);
            $this->assertStringContainsString(__('view_compendium.class.show.npcs_no_exceptions'), $row);
            $this->assertStringNotContainsString(__('view_compendium.class.show.npcs_no_data'), $row);
        } finally {
            $scenario['cleanUp']();
        }
    }

    /**
     * A dungeon carrying no characteristic data of its own, plus one NPC of every classification this
     * page treats differently and two of the chosen class's characteristics to hand out among them.
     *
     * Paladin on purpose: it has six characteristics but neither a counter nor a reflect section, so
     * the crowd control table is the only place an NPC name can appear and `assertDontSeeText` on an
     * NPC actually means "no row listed it".
     *
     * @return array{
     *     characterClass: CharacterClass,
     *     dungeon: Dungeon,
     *     user: User,
     *     characteristicIds: array<int, int>,
     *     tauntCharacteristicId: int,
     *     spells: Collection<int, Spell>,
     *     elites: array<int, Npc>,
     *     normal: Npc,
     *     boss: Npc,
     *     cleanUp: callable(): void,
     * }
     */
    private function arrangeCharacteristicScenario(): array
    {
        $characterClass = CharacterClass::where('key', CharacterClass::CHARACTER_CLASS_PALADIN)->firstOrFail();

        $defaultGameVersion = GameVersion::getDefaultGameVersion();

        $classSpells = Spell::where('category', sprintf('spellcategory.%s', $characterClass->key))
            ->whereNotNull('characteristic_id')
            ->where('game_version_id', $defaultGameVersion->id)
            ->get();

        $classCharacteristicIds = $classSpells->pluck('characteristic_id')->unique()->values();

        // Paladin has a taunt (Hand of Reckoning). Taunt is no longer special-cased in the universe,
        // but it is still kept separate from the two generic ids handed out below so a scenario can
        // independently observe an NPC via taunt only - proving it stays eligible for other
        // characteristics' no-effect rows, and that taunt's own row still reports normally
        $tauntCharacteristicId   = Characteristic::ALL[Characteristic::CHARACTERISTIC_TAUNT];
        $usableCharacteristicIds = $classCharacteristicIds->reject(
            static fn(int $characteristicId) => $characteristicId === $tauntCharacteristicId,
        )->values();
        $this->assertGreaterThanOrEqual(2, $usableCharacteristicIds->count());
        $this->assertTrue($classCharacteristicIds->contains($tauntCharacteristicId));

        [$dungeon, $elites, $normal, $boss] = $this->findDungeonWithEveryClassification(
            $defaultGameVersion,
            $classCharacteristicIds->all(),
        );

        $user              = User::findOrFail(1);
        $originalDungeonId = $user->dungeon_id;
        $user->dungeon_id  = $dungeon->id;
        $user->save();

        return [
            'characterClass'        => $characterClass,
            'dungeon'               => $dungeon,
            'user'                  => $user->fresh(),
            'characteristicIds'     => $usableCharacteristicIds->take(2)->all(),
            'tauntCharacteristicId' => $tauntCharacteristicId,
            'spells'                => $classSpells,
            'elites'                => $elites,
            'normal'                => $normal,
            'boss'                  => $boss,
            // Only ever remove what this test created - NpcCharacteristic rows are combat-log-derived
            // and a delete of somebody else's row is not recoverable from the seeders
            'cleanUp' => function () use ($user, $originalDungeonId): void {
                foreach ($this->createdNpcCharacteristics as [$npcId, $characteristicId]) {
                    NpcCharacteristic::where('npc_id', $npcId)
                        ->where('characteristic_id', $characteristicId)
                        ->delete();
                }

                $this->createdNpcCharacteristics = [];

                $user->dungeon_id = $originalDungeonId;
                $user->save();
            },
        ];
    }

    /**
     * The page reports elites/rares and bosses in opposite directions and leaves normal trash out
     * altogether, so a scenario needs all three. The dungeon must also carry no characteristic data of
     * its own: every pre-existing row would widen the universe and put NPC names on the page that the
     * assertions expect to be absent.
     *
     * @param  array<int, int>                                       $classCharacteristicIds
     * @return array{0: Dungeon, 1: array<int, Npc>, 2: Npc, 3: Npc}
     */
    private function findDungeonWithEveryClassification(GameVersion $gameVersion, array $classCharacteristicIds): array
    {
        $eliteClassificationIds = [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
        ];
        $bossClassificationIds = [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
        ];
        $normalClassificationId = NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL];

        $dungeons = Dungeon::query()
            ->where('challenge_mode_id', '>', 0)
            ->where('active', true)
            ->orderByDesc('id')
            ->get();

        foreach ($dungeons as $dungeon) {
            $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

            if ($mappingVersion === null) {
                continue;
            }

            // Dedupe on the *rendered* name. `npcs`.`name` is a per-id translation key, so a
            // unique('name') never collapses anything - while lang/en_US/npcs.php maps hundreds of
            // distinct ids onto a single name (Scarlet Infantry covers eleven). Two of those picked
            // into one scenario would make assertDontSeeText ambiguous, since it cannot tell which
            // id put the name on the page.
            $npcs = Npc::query()
                ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
                ->where('enemies.mapping_version_id', $mappingVersion->id)
                ->select('npcs.*')
                ->distinct()
                ->get()
                ->unique(static fn(Npc $npc) => __($npc->name));

            $hasCharacteristicData = NpcCharacteristic::whereIn('npc_id', $npcs->pluck('id'))
                ->whereIn('characteristic_id', $classCharacteristicIds)
                ->exists();

            if ($hasCharacteristicData) {
                continue;
            }

            $elites = $npcs->whereIn('classification_id', $eliteClassificationIds)->values()->take(3);
            $normal = $npcs->where('classification_id', $normalClassificationId)->first();
            $boss   = $npcs->whereIn('classification_id', $bossClassificationIds)->first();

            if ($elites->count() === 3 && $normal !== null && $boss !== null) {
                return [$dungeon, $elites->all(), $normal, $boss];
            }
        }

        $this->fail('No dungeon found with three elites, a normal NPC, a boss and no characteristic data of its own');
    }

    #[Test]
    public function showDungeon_givenMultipleSpells_ordersRowsByRenderedSpellName(): void
    {
        // Arrange
        $scenario = $this->arrangeCharacteristicScenario();

        try {
            $expectedOrder = $scenario['spells']->sortBy(static fn(Spell $spell) => __($spell->name))->values();

            // Without this the test could pass on a query that never sorts at all - it only means
            // something while the two orders actually differ
            $this->assertNotSame(
                $scenario['spells']->sortBy('id')->pluck('id')->all(),
                $expectedOrder->pluck('id')->all(),
                'This class\'s spells sort identically by id and by rendered name, so this test cannot detect the regression',
            );

            // Act
            $response = $this->actingAs($scenario['user'])->get(route('compendium.class.show.dungeon', [
                'characterClass' => $scenario['characterClass'],
                'dungeon'        => $scenario['dungeon'],
            ]));

            // Assert - `spells`.`name` holds a translation key, so an orderBy('name') in the query
            // would sort spells.103828 ahead of spells.1715 ahead of spells.355: it looks sorted and
            // is not. The sort has to happen after __() resolution, which is what this pins.
            $response->assertOk();
            $content = (string)$response->getContent();

            $positions = $expectedOrder->map(
                fn(Spell $spell) => strpos($content, sprintf('href="%s"', route('spell.compendium.show', $spell))),
            );

            $this->assertNotContains(false, $positions->all(), 'Not every spell rendered a link');
            $this->assertSame($positions->sort()->values()->all(), $positions->all());
        } finally {
            $scenario['cleanUp']();
        }
    }

    /**
     * The one table row that belongs to a spell.
     *
     * Page-level assertions are nearly worthless on this table: every characteristic of the class
     * renders a row, so "No data" and "Nothing unexpected" legitimately appear several times over.
     * An assertSeeText for either would still pass with the arrangement deleted or the grouping
     * broken - it only proves *some* row reached that state, never that this one did.
     */
    private function getRowHtmlForSpell(string $content, Spell $spell): string
    {
        $needle = sprintf('href="%s"', route('spell.compendium.show', $spell));

        foreach (explode('<tr', $content) as $row) {
            if (str_contains($row, $needle)) {
                return $row;
            }
        }

        $this->fail(sprintf('No table row found for spell %s', __($spell->name)));
    }

    /**
     * @param array<int, int> $characteristicIdByNpcId
     */
    private function assignCharacteristics(array $characteristicIdByNpcId): void
    {
        foreach ($characteristicIdByNpcId as $npcId => $characteristicId) {
            NpcCharacteristic::create([
                'npc_id'            => $npcId,
                'characteristic_id' => $characteristicId,
            ]);

            $this->createdNpcCharacteristics[] = [$npcId, $characteristicId];
        }
    }
}
