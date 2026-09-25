<?php

namespace Tests\Unit\App\Service\CombatLog\DataExtractors\Characteristics;

use App\Models\Characteristic;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellEffect;
use App\Models\Spell\SpellMechanic;
use App\Service\CombatLog\DataExtractors\Characteristics\CharacteristicEvidenceRule;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('NpcCharacteristicDataExtractor')]
final class CharacteristicEvidenceRuleTest extends PublicTestCase
{
    private const int EFFECT_TYPE_APPLY_AURA = 6;

    private const int EFFECT_TYPE_SCHOOL_DAMAGE = 2;

    private const int EFFECT_TYPE_KNOCK_BACK = 98;

    private const int AURA_MOD_DECREASE_SPEED = 33;

    private const int AURA_PERIODIC_DAMAGE = 3;

    /** Rake's damage amplifier, the rider that rides along with Infected Wounds' snare. */
    private const int AURA_MOD_AUTO_ATTACK_DAMAGE = 118;

    private const string CURATED_SPELLS_PATH = 'seeders/dungeondata/spells.json';

    /**
     * The curated spells whose application on an NPC proves the characteristic they carry, as the rule
     * judges them today. Pinned by id rather than counted, so curating a new spell - or changing the
     * effects of one - has to take a deliberate position on whether it is evidence.
     *
     * @var array<int, int>
     */
    private const array CONCLUSIVE_SPELL_IDS = [
        339, 408, 453, 605, 853, 1098, 1513, 1715, 1776, 1833, 2094, 2637, 2649, 5116, 5211, 5246,
        5484, 6770, 6795, 10326, 15487, 20066, 26679, 28271, 28272, 30283, 31589, 31935, 40135,
        45524, 47476, 49576, 51490, 56222, 61305, 61721, 62124, 64044, 89766, 102359, 111673, 115078,
        115770, 119381, 126819, 145532, 147732, 161353, 161354, 179057, 185245, 196840, 211881, 213691,
        217832, 221562, 248920, 277787, 277792, 360806, 391622, 460392,
    ];

    /**
     * The curated spells an aura application can never prove, each for one of two reasons: the aura it
     * puts on the NPC is a rider that lands whether or not the crowd control did (Infected Wounds 58180,
     * Garrote 703), or the spell applies no aura to the NPC at all, so the aura seen in the log belongs to
     * a different spell id than the one curated (Typhoon 132469, Solar Beam 78675, Crippling Poison 3408,
     * Frostbolt 116).
     *
     * @var array<int, int>
     */
    private const array INCONCLUSIVE_SPELL_IDS = [
        116, 355, 703, 3408, 5782, 18100, 19577, 30108, 44614, 46968, 47481, 51533, 58180, 78675,
        103828, 107570, 108199, 115546, 115750, 116095, 132469, 187650, 191427, 192058, 371032,
        375058, 385952,
    ];

    #[Test]
    public function isConclusive_givenTheSpellLevelMechanicOfItsCharacteristic_returnsTrue(): void
    {
        // Arrange - a snare the server rejects outright on a snare-immune target, riders and all
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SLOW, SpellMechanic::Snared, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_AUTO_ATTACK_DAMAGE],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isConclusive_givenOnlyTheCrowdControlAura_returnsTrue(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SLOW, null, [
            [self::EFFECT_TYPE_SCHOOL_DAMAGE, 0],
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isConclusive_givenARiderAuraAlongsideTheCrowdControl_returnsFalse(): void
    {
        // Arrange - Infected Wounds: the damage amplifier lands on a boss immune to the snare
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SLOW, null, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_AUTO_ATTACK_DAMAGE],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusive_givenAMechanicOfAnotherCharacteristic_returnsFalse(): void
    {
        // Arrange - Garrote is curated as a silence, but its mechanic is its bleed
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SILENCE, SpellMechanic::Bleeding, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_PERIODIC_DAMAGE],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusive_givenNoEffectsAtAll_returnsFalse(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SILENCE, null, []);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusive_givenAKnockSpellApplyingAnAura_returnsTrue(): void
    {
        // Arrange - Thunderstorm knocks back and dazes; the daze is the only trace the knock leaves
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_KNOCK, null, [
            [self::EFFECT_TYPE_SCHOOL_DAMAGE, 0],
            [self::EFFECT_TYPE_KNOCK_BACK, 0],
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isConclusive_givenAKnockSpellApplyingNoAura_returnsFalse(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_KNOCK, null, [
            [self::EFFECT_TYPE_SCHOOL_DAMAGE, 0],
            [self::EFFECT_TYPE_KNOCK_BACK, 0],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusiveOn_givenAKnockSpellOnABoss_returnsFalse(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_KNOCK, null, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);
        $npc = $this->makeNpc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act
        $result = CharacteristicEvidenceRule::isConclusiveOn($spell, $npc);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusiveOn_givenAKnockSpellOnAFinalBoss_returnsFalse(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_KNOCK, null, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);
        $npc = $this->makeNpc(NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS);

        // Act
        $result = CharacteristicEvidenceRule::isConclusiveOn($spell, $npc);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusiveOn_givenAKnockSpellOnTrash_returnsTrue(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_KNOCK, null, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);
        $npc = $this->makeNpc(NpcClassification::NPC_CLASSIFICATION_ELITE);

        // Act
        $result = CharacteristicEvidenceRule::isConclusiveOn($spell, $npc);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isConclusiveOn_givenAnotherCharacteristicOnABoss_returnsTrue(): void
    {
        // Arrange
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SLOW, SpellMechanic::Snared, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);
        $npc = $this->makeNpc(NpcClassification::NPC_CLASSIFICATION_BOSS);

        // Act
        $result = CharacteristicEvidenceRule::isConclusiveOn($spell, $npc);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isConclusive_givenAnUncuratedCharacteristicAura_returnsFalse(): void
    {
        // Arrange - a silence proven by something that is not a silence aura
        $spell = $this->makeSpell(Characteristic::CHARACTERISTIC_SILENCE, null, [
            [self::EFFECT_TYPE_APPLY_AURA, self::AURA_MOD_DECREASE_SPEED],
        ]);

        // Act
        $result = CharacteristicEvidenceRule::isConclusive($spell);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isConclusive_givenTheCuratedSpellList_returnsTheReviewedVerdicts(): void
    {
        // Arrange
        $curatedSpells = $this->getCuratedCharacteristicSpells();

        // Act
        $conclusiveIds   = [];
        $inconclusiveIds = [];

        foreach ($curatedSpells as $spell) {
            if (CharacteristicEvidenceRule::isConclusive($spell)) {
                $conclusiveIds[] = $spell->id;
            } else {
                $inconclusiveIds[] = $spell->id;
            }
        }

        sort($conclusiveIds);
        sort($inconclusiveIds);

        // Assert
        $expectedConclusiveIds = self::CONCLUSIVE_SPELL_IDS;
        sort($expectedConclusiveIds);
        $expectedInconclusiveIds = self::INCONCLUSIVE_SPELL_IDS;
        sort($expectedInconclusiveIds);

        $this->assertSame(
            $expectedConclusiveIds,
            $conclusiveIds,
            'A curated characteristic spell changed verdict - review whether its application really proves the characteristic.',
        );
        $this->assertSame(
            $expectedInconclusiveIds,
            $inconclusiveIds,
            'A curated characteristic spell changed verdict - review whether its application really proves the characteristic.',
        );
    }

    /**
     * Every spell the seeders curate a characteristic for, hydrated from the json rather than the
     * database: the verdicts above are about the data that ships, not about whatever a local database
     * happens to hold.
     *
     * @return array<int, Spell>
     */
    private function getCuratedCharacteristicSpells(): array
    {
        $contents = file_get_contents(database_path(self::CURATED_SPELLS_PATH));
        $this->assertNotFalse($contents);

        $spells = [];

        foreach (json_decode($contents, true, 512, JSON_THROW_ON_ERROR) as $data) {
            if ($data['characteristic_id'] === null) {
                continue;
            }

            $spell = new Spell($data);
            $spell->setRelation('spellEffects', new Collection(array_map(
                static fn(array $effect): SpellEffect => new SpellEffect($effect),
                $data['spell_effects'],
            )));

            $spells[] = $spell;
        }

        return $spells;
    }

    /**
     * @param array<int, array{0: int, 1: int}> $effects effect type and aura type per effect
     */
    private function makeSpell(string $characteristicKey, ?SpellMechanic $mechanic, array $effects): Spell
    {
        $spell = new Spell([
            'id'                => 999999912,
            'characteristic_id' => Characteristic::ALL[$characteristicKey],
            'mechanic'          => $mechanic?->translationKey(),
        ]);

        $spell->setRelation('spellEffects', new Collection(array_map(
            static fn(array $effect): SpellEffect => new SpellEffect([
                'effect_type' => $effect[0],
                'aura_type'   => $effect[1],
            ]),
            $effects,
        )));

        return $spell;
    }

    private function makeNpc(string $classificationKey): Npc
    {
        return new Npc([
            'id'                => 999999913,
            'classification_id' => NpcClassification::ALL[$classificationKey],
        ]);
    }
}
