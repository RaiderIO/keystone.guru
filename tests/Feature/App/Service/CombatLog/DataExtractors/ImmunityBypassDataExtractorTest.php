<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Dungeon;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\ImmunityBypassDataExtractor;
use App\Service\CombatLog\DataExtractors\ImmunityBypasses\ImmunityDefinitionInterface;
use App\Service\CombatLog\DataExtractors\ImmunityBypasses\ImmunityDefinitions;
use App\Service\CombatLog\DataExtractors\Logging\ImmunityBypassDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('ImmunityBypassDataExtractor')]
final class ImmunityBypassDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/immunity-bypass-test.log';

    private const string PLAYER_GUID       = 'Player-1084-0A5F8492';
    private const string OTHER_PLAYER_GUID = 'Player-1084-0B123456';
    private const string CREATURE_GUID     = 'Creature-0-4237-1209-2796-76149-0000293D52';

    /** @var int The npc id embedded in CREATURE_GUID - Dread Raven, present in the seeded Skyreach mapping */
    private const int CREATURE_NPC_ID = 76149;

    /** @var int The first test spell id - all test spells live in [TEST_SPELL_ID_FIRST, TEST_SPELL_ID_LAST] */
    private const int TEST_SPELL_ID_FIRST = 9991001;

    private const int TEST_SPELL_ID_LAST = 9991040;

    /** @var int Shadow, so it is covered by both the full and the magic-only immunities */
    private const int SCHOOL_SHADOW = Spell::SCHOOL_SHADOW;

    private ImmunityBypassDataExtractor $extractor;

    private ExtractedDataResult $result;

    private DataExtractionCurrentDungeon $currentDungeon;

    private MockInterface $log;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $log = Mockery::mock(ImmunityBypassDataExtractorLoggingInterface::class);
        $log->shouldIgnoreMissing();
        $this->log = $log;
        $this->app->bind(ImmunityBypassDataExtractorLoggingInterface::class, fn() => $this->log);

        $this->cleanUpTestData();

        $this->extractor = new ImmunityBypassDataExtractor();
        $this->result    = new ExtractedDataResult();

        $dungeon              = Dungeon::first();
        $this->currentDungeon = new DataExtractionCurrentDungeon($dungeon);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function extractData_givenNpcDamageInsideDivineShield_setsImmunityBypass(): void
    {
        // Arrange
        $spellId = 9991001;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);

        // Assert - the bypassing spell is assigned to the NPC that cast it, so it shows up on its compendium page
        $this->assertTrue(NpcSpell::where('npc_id', self::CREATURE_NPC_ID)->where('spell_id', $spellId)->exists());
        $this->assertTrue(SpellDungeon::where('spell_id', $spellId)->where('dungeon_id', $this->currentDungeon->dungeon->id)->exists());
        $this->assertSame(1, CombatLogNpcEvent::where('npc_id', self::CREATURE_NPC_ID)
            ->where('event_type', CombatLogNpcEventType::SpellAssigned)
            ->where('model_class', Spell::class)
            ->where('model_id', $spellId)
            ->count());
    }

    #[Test]
    public function extractData_givenNpcDamageBeforeImmunityApplied_doesNotSetImmunityBypass(): void
    {
        // Arrange
        $spellId = 9991002;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->npcDamage(0, $spellId, 'Unstoppable Force'),
            $this->immunityApplied(1000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->immunityRemoved(9000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenNpcDamageAfterImmunityRemoved_doesNotSetImmunityBypass(): void
    {
        // Arrange
        $spellId = 9991003;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(8500, $spellId, 'Unstoppable Force'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenNpcDamageWithinEpsilonOfImmunityApplication_doesNotSetImmunityBypass(): void
    {
        // Arrange - the log orders same-tick lines arbitrarily, so this hit may well have landed before the immunity
        $spellId = 9991004;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(ImmunityBypassDataExtractor::EPSILON_MS - 1, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenNpcDamageWithinEpsilonOfImmunityRemoval_doesNotSetImmunityBypass(): void
    {
        // Arrange - equally ambiguous at the other end of the window
        $spellId = 9991005;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(8000 - (ImmunityBypassDataExtractor::EPSILON_MS - 1), $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenPhysicalDamageInsideBlessingOfSpellwarding_doesNotSetImmunityBypass(): void
    {
        // Arrange - Spellwarding never claimed to stop physical damage, so this is expected behaviour
        $spellId = 9991006;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_BLESSING_OF_SPELLWARDING, 'Blessing of Spellwarding'),
            $this->npcDamage(2000, $spellId, 'Cleave', Spell::SCHOOL_PHYSICAL),
            $this->immunityRemoved(10000, Spell::SPELL_BLESSING_OF_SPELLWARDING, 'Blessing of Spellwarding'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenMagicDamageInsideBlessingOfSpellwarding_setsImmunityBypass(): void
    {
        // Arrange
        $spellId = 9991007;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_BLESSING_OF_SPELLWARDING, 'Blessing of Spellwarding'),
            $this->npcDamage(2000, $spellId, 'Void Bolt'),
            $this->immunityRemoved(10000, Spell::SPELL_BLESSING_OF_SPELLWARDING, 'Blessing of Spellwarding'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_BLESSING_OF_SPELLWARDING, SpellProperty::BypassBlessingOfSpellwarding);
    }

    #[Test]
    public function extractData_givenMagicDamageInsideAntiMagicShell_doesNotSetImmunityBypass(): void
    {
        // Arrange - Anti-Magic Shell absorbs magic damage rather than being immune to it, so damage landing is how it
        // works, not a failure of it
        $spellId = 9991008;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_ANTI_MAGIC_SHELL, 'Anti-Magic Shell'),
            $this->npcDamage(2000, $spellId, 'Void Bolt'),
            $this->immunityRemoved(5000, Spell::SPELL_ANTI_MAGIC_SHELL, 'Anti-Magic Shell'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenHarmfulAuraInsideAntiMagicShell_setsImmunityBypass(): void
    {
        // Arrange - harmful magic *effects* are what Anti-Magic Shell is immune to
        $spellId = 9991009;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_ANTI_MAGIC_SHELL, 'Anti-Magic Shell'),
            $this->npcDebuffApplied(2000, $spellId, 'Mind Flay'),
            $this->immunityRemoved(5000, Spell::SPELL_ANTI_MAGIC_SHELL, 'Anti-Magic Shell'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_ANTI_MAGIC_SHELL, SpellProperty::BypassAntiMagicShell);
    }

    #[Test]
    public function extractData_givenHarmfulAuraInsideAspectOfTheTurtle_doesNotSetImmunityBypass(): void
    {
        // Arrange - Aspect of the Turtle is a damage immunity, it never stopped debuffs
        $spellId = 9991010;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_ASPECT_OF_THE_TURTLE, 'Aspect of the Turtle'),
            $this->npcDebuffApplied(2000, $spellId, 'Mind Flay'),
            $this->immunityRemoved(8000, Spell::SPELL_ASPECT_OF_THE_TURTLE, 'Aspect of the Turtle'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenPeriodicDamageInsideDivineShield_doesNotSetImmunityBypass(): void
    {
        // Arrange - a damage over time effect applied before the immunity is the documented partial case; only the
        // application of a fresh harmful aura inside the window is a property of the ability
        $spellId = 9991011;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcPeriodicDamage(2000, $spellId, 'Corruption'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenMissedSpellInsideDivineShield_doesNotSetImmunityBypass(): void
    {
        // Arrange - the immunity doing exactly what it should
        $spellId = 9991012;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcMissed(2000, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenZeroDamageInsideDivineShield_doesNotSetImmunityBypass(): void
    {
        // Arrange - nothing actually landed
        $spellId = 9991013;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force', self::SCHOOL_SHADOW, 0),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenDamageOnAnotherPlayerDuringImmunity_doesNotSetImmunityBypass(): void
    {
        // Arrange - the immunity belongs to a different player entirely
        $spellId = 9991014;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force', self::SCHOOL_SHADOW, 12345, self::OTHER_PLAYER_GUID),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenPlayerSourcedDamageDuringImmunity_doesNotSetImmunityBypass(): void
    {
        // Arrange - only NPC abilities are of interest to the compendium
        $spellId = 9991015;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Sanguine Ichor', self::SCHOOL_SHADOW, 12345, self::PLAYER_GUID, self::OTHER_PLAYER_GUID),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenDamageFromACastThatResolvedBeforeTheImmunity_doesNotSetImmunityBypass(): void
    {
        // Arrange - a projectile already in flight when the immunity went up. Validated against a real log: an Arcane
        // Bolt resolved 127ms before Aspect of the Turtle and landed 367ms into the window, while the *next* Arcane
        // Bolt in that same window was cleanly missed as immune
        $spellId = 9991022;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $spellId, 'Arcane Bolt'),
            $this->immunityApplied(127, Spell::SPELL_ASPECT_OF_THE_TURTLE, 'Aspect of the Turtle'),
            $this->npcDamage(494, $spellId, 'Arcane Bolt'),
            $this->immunityRemoved(8127, Spell::SPELL_ASPECT_OF_THE_TURTLE, 'Aspect of the Turtle'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenDamageFromACastThatResolvedInsideTheImmunity_setsImmunityBypass(): void
    {
        // Arrange - the intended scenario: the player pops the immunity while the cast bar is up, so the ability
        // resolves *inside* the window and still lands
        $spellId = 9991023;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcCastSuccess(2000, $spellId, 'Unstoppable Force'),
            $this->npcDamage(2010, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);
    }

    #[Test]
    public function extractData_givenAStaleCastLongBeforeTheImmunity_setsImmunityBypass(): void
    {
        // Arrange - a cast older than the provenance window cannot explain this hit as in-flight, so the hit is
        // judged on the window alone
        $spellId = 9991024;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $spellId, 'Unstoppable Force'),
            $this->immunityApplied(
                ImmunityBypassDataExtractor::CAST_PROVENANCE_MAX_AGE_MS,
                Spell::SPELL_DIVINE_SHIELD,
                'Divine Shield',
            ),
            $this->npcDamage(ImmunityBypassDataExtractor::CAST_PROVENANCE_MAX_AGE_MS + 2000, $spellId, 'Unstoppable Force'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);
    }

    #[Test]
    public function extractData_givenDamageAfterWindowOutlivedItsDuration_doesNotSetImmunityBypass(): void
    {
        // Arrange - the removal line was never logged, so the window can only have expired on its own
        $spellId = 9991016;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(9000, $spellId, 'Unstoppable Force'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenWindowNeverClosedBeforeEndOfLog_stillSetsImmunityBypass(): void
    {
        // Arrange - a truncated log must not silently swallow the detections gathered inside the window
        $spellId = 9991017;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);
    }

    #[Test]
    public function extractData_givenDamageInsideTwoLayeredImmunities_setsBothImmunityBypasses(): void
    {
        // Arrange - Blessing of Protection layered under Divine Shield; a physical hit bypasses both
        $spellId = 9991018;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->immunityApplied(500, Spell::SPELL_BLESSING_OF_PROTECTION, 'Blessing of Protection'),
            $this->npcDamage(2000, $spellId, 'Cleave', Spell::SCHOOL_PHYSICAL),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->immunityRemoved(8500, Spell::SPELL_BLESSING_OF_PROTECTION, 'Blessing of Protection'),
        ]);

        // Assert
        $this->assertSame(2, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded(
            $spellId,
            Spell::IMMUNITY_DIVINE_SHIELD | Spell::IMMUNITY_BLESSING_OF_PROTECTION,
            SpellProperty::BypassDivineShield,
        );
        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id' => $spellId,
            'property' => SpellProperty::BypassBlessingOfProtection->value,
        ], 'combatlog');
    }

    #[Test]
    public function extractData_givenTheSameBypassTwice_recordsItOnce(): void
    {
        // Arrange
        $spellId = 9991019;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force'),
            $this->npcDamage(3000, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);
    }

    #[Test]
    public function extractData_givenUnknownBuffDuringDamage_doesNotSetImmunityBypass(): void
    {
        // Arrange - an ordinary buff must not open a window
        $spellId = 9991020;
        $buffId  = 9991021;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->immunityApplied(0, $buffId, 'Power Word: Fortitude'),
            $this->npcDamage(2000, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(8000, $buffId, 'Power Word: Fortitude'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenARecastInsideTheWindowAfterAnEarlierCast_doesNotSetImmunityBypass(): void
    {
        // Arrange - the earlier projectile is still in flight; a re-cast inside the window must not launder it
        $spellId = 9991025;
        $this->createTestSpell($spellId);

        // Act
        $this->runExtract([
            $this->npcCastSuccess(0, $spellId, 'Arcane Bolt'),
            $this->immunityApplied(200, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcCastSuccess(1000, $spellId, 'Arcane Bolt'),
            $this->npcDamage(1200, $spellId, 'Arcane Bolt'),
            $this->immunityRemoved(8200, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenManyOtherCastersRememberedSinceTheCast_stillDoesNotSetImmunityBypass(): void
    {
        // Arrange - provenance expires by age, and a live entry must survive no matter how many *other* caster/spell
        // keys were remembered after it. A caster guid is per mob instance, so a long log remembers a great many
        $spellId = 9991030;
        $this->createTestSpell($spellId);

        $events = [$this->npcCastSuccess(0, $spellId, 'Arcane Bolt')];

        // Well past the map size that used to trigger the rebuild-everything prune, all inside the provenance window
        for ($otherCaster = 1; $otherCaster <= 1500; $otherCaster++) {
            $events[] = $this->npcCastSuccess($otherCaster, $spellId, 'Arcane Bolt', $this->creatureGuid($otherCaster));
        }

        $events[] = $this->immunityApplied(2000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield');
        $events[] = $this->npcDamage(2500, $spellId, 'Arcane Bolt');
        $events[] = $this->immunityRemoved(10000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield');

        // Act
        $this->runExtract($events);

        // Assert - the original cast resolved before the window, so what landed was already in flight
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenCastsSpanningLongerThanTheProvenanceWindow_forgetsTheExpiredOnes(): void
    {
        // Arrange - the map must be bounded by the provenance window rather than by a size threshold, otherwise it
        // grows with the length of the log
        $spellId = 9991031;
        $this->createTestSpell($spellId);
        $stepMs = 100;
        $casts  = 300;

        // Act
        $this->extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        for ($cast = 0; $cast < $casts; $cast++) {
            $this->extractor->extractData(
                $this->result,
                $this->currentDungeon,
                $this->npcCastSuccess($cast * $stepMs, $spellId, 'Arcane Bolt', $this->creatureGuid($cast)),
            );
        }

        // Assert - only the casts within CAST_PROVENANCE_MAX_AGE_MS of the most recent one are still remembered
        $this->assertSame(
            intdiv(ImmunityBypassDataExtractor::CAST_PROVENANCE_MAX_AGE_MS, $stepMs) + 1,
            count($this->rememberedNpcCastSuccesses()),
        );

        // Assert - and the whole map is dropped at the end of the combat log
        $this->extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);
        $this->assertSame([], $this->rememberedNpcCastSuccesses());
    }

    #[Test]
    public function extractData_givenACastRememberedFromAPreviousCombatLog_doesNotAffectTheNextOne(): void
    {
        // Arrange - the extractor instance is reused for every file the extraction command walks
        $spellId = 9991026;
        $this->createTestSpell($spellId);

        // Act - a first log leaves a cast behind, the second would be vetoed by it if it survived afterExtract
        $this->runExtract([
            $this->npcCastSuccess(0, $spellId, 'Unstoppable Force'),
        ]);
        // The second log starts later than that leftover cast, so a surviving one would veto this hit as in-flight
        $this->runExtract([
            $this->immunityApplied(2000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage(4000, $spellId, 'Unstoppable Force'),
            $this->immunityRemoved(10000, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
        ]);

        // Assert
        $this->assertSame(1, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertBypassRecorded($spellId, Spell::IMMUNITY_DIVINE_SHIELD, SpellProperty::BypassDivineShield);
    }

    #[Test]
    public function extractData_givenDamageWithinEpsilonOfAnUnloggedExpiry_doesNotSetImmunityBypass(): void
    {
        // Arrange - no removal line, but where the window ended is known exactly, so the trailing epsilon applies
        $spellId = 9991027;
        $this->createTestSpell($spellId);
        $expiryMs = 8000;

        // Act
        $this->runExtract([
            $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'),
            $this->npcDamage($expiryMs - (ImmunityBypassDataExtractor::EPSILON_MS - 1), $spellId, 'Unstoppable Force'),
            $this->npcDamage($expiryMs + 5000, 9991028, 'Unrelated'),
        ]);

        // Assert
        $this->assertSame(0, $this->result->toArray()['addedSpellImmunityBypasses']);
        $this->assertNoBypassRecorded($spellId);
    }

    #[Test]
    public function extractData_givenAZoneChangeAfterTheHit_attributesTheSpellToTheDungeonItWasSeenIn(): void
    {
        // Arrange - a ZoneChange flushes the open windows, but only after it has moved the current dungeon on
        $spellId = 9991029;
        $this->createTestSpell($spellId);
        $otherDungeon = Dungeon::where('id', '!=', $this->currentDungeon->dungeon->id)->firstOrFail();

        // Act
        $this->extractor->beforeExtract($this->result, self::COMBAT_LOG_PATH);
        $this->extractor->extractData($this->result, $this->currentDungeon, $this->immunityApplied(0, Spell::SPELL_DIVINE_SHIELD, 'Divine Shield'));
        $this->extractor->extractData($this->result, $this->currentDungeon, $this->npcDamage(2000, $spellId, 'Unstoppable Force'));
        $this->extractor->extractData($this->result, new DataExtractionCurrentDungeon($otherDungeon), $this->zoneChange(4000));
        $this->extractor->afterExtract($this->result, self::COMBAT_LOG_PATH);

        // Assert
        $this->assertTrue(SpellDungeon::where('spell_id', $spellId)->where('dungeon_id', $this->currentDungeon->dungeon->id)->exists());
        $this->assertFalse(SpellDungeon::where('spell_id', $spellId)->where('dungeon_id', $otherDungeon->id)->exists());
    }

    #[Test]
    public function immunityDefinitions_givenTheCanonicalSpellList_coversEveryImmunitySpellExactlyOnce(): void
    {
        // Arrange
        $definedBuffSpellIds = ImmunityDefinitions::all()
            ->flatMap(fn(ImmunityDefinitionInterface $definition) => $definition->getBuffSpellIds())
            ->all();

        // Act
        sort($definedBuffSpellIds);
        $canonicalSpellIds = Spell::IMMUNITY_SPELLS;
        sort($canonicalSpellIds);

        // Assert - a spell id added to the constant without a definition would silently never open a window
        $this->assertSame($canonicalSpellIds, $definedBuffSpellIds);
        $this->assertSame(
            ImmunityDefinitions::all()->count(),
            ImmunityDefinitions::all()
                ->map(fn(ImmunityDefinitionInterface $definition) => $definition->getProperty()->value)
                ->unique()
                ->count(),
            'Every immunity must have its own SpellProperty',
        );
    }

    /**
     * Runs the full extract lifecycle: beforeExtract → extractData (one or more events) → afterExtract.
     *
     * @param BaseEvent[] $events
     */
    private function runExtract(array $events, string $combatLogPath = self::COMBAT_LOG_PATH): void
    {
        $this->extractor->beforeExtract($this->result, $combatLogPath);
        foreach ($events as $event) {
            $this->extractor->extractData($this->result, $this->currentDungeon, $event);
        }
        $this->extractor->afterExtract($this->result, $combatLogPath);
    }

    /**
     * A distinct creature guid for the same npc id - a guid identifies one mob *instance*, so every pack member and
     * every respawn is its own provenance key.
     */
    private function creatureGuid(int $spawnIndex): string
    {
        return sprintf('Creature-0-4237-1209-2796-%d-%010X', self::CREATURE_NPC_ID, $spawnIndex);
    }

    /**
     * The extractor's private provenance map - the structure whose growth this test file guards.
     *
     * @return array<string, array<int, int>>
     */
    private function rememberedNpcCastSuccesses(): array
    {
        /** @var array<string, array<int, int>> $remembered */
        $remembered = new ReflectionProperty(ImmunityBypassDataExtractor::class, 'recentNpcCastSuccesses')
            ->getValue($this->extractor);

        return $remembered;
    }

    private function assertBypassRecorded(int $spellId, int $immunityMask, SpellProperty $property): void
    {
        $this->assertDatabaseHas('spells', [
            'id'                       => $spellId,
            'bypasses_immunities_mask' => $immunityMask,
        ]);

        $this->assertDatabaseHas('combat_log_spell_property_observations', [
            'spell_id'        => $spellId,
            'property'        => $property->value,
            'combat_log_path' => self::COMBAT_LOG_PATH,
        ], 'combatlog');

        $this->assertSame(
            1,
            CombatLogSpellEvent::where('spell_id', $spellId)
                ->where('event_type', CombatLogSpellEventType::PropertyChanged)
                ->where('property', $property)
                ->count(),
        );
    }

    private function assertNoBypassRecorded(int $spellId): void
    {
        $this->assertDatabaseHas('spells', [
            'id'                       => $spellId,
            'bypasses_immunities_mask' => 0,
        ]);

        $this->assertDatabaseMissing('combat_log_spell_property_observations', [
            'spell_id' => $spellId,
        ], 'combatlog');

        $this->assertDatabaseMissing('combat_log_spell_events', [
            'spell_id' => $spellId,
        ], 'combatlog');
    }

    private function createTestSpell(int $spellId, ?string $category = null): Spell
    {
        return Spell::create([
            'id'              => $spellId,
            'game_version_id' => 1,
            'category'        => $category,
            'dispel_type'     => 'none',
            'icon_name'       => 'inv_misc_questionmark',
            'name'            => sprintf('Test Spell %d', $spellId),
            'schools_mask'    => self::SCHOOL_SHADOW,
        ]);
    }

    private function cleanUpTestData(): void
    {
        CombatLogSpellPropertyObservation::whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        CombatLogSpellEvent::whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        CombatLogNpcEvent::where('model_class', Spell::class)->whereBetween('model_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        NpcSpell::query()->whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        SpellDungeon::query()->whereBetween('spell_id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
        Spell::query()->whereBetween('id', [self::TEST_SPELL_ID_FIRST, self::TEST_SPELL_ID_LAST])->delete();
    }

    /**
     * A player applying an immunity buff to themselves.
     */
    private function immunityApplied(int $offsetMs, int $spellId, string $spellName, string $playerGuid = self::PLAYER_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_APPLIED,%s,%s,%d,"%s",0x2,BUFF',
            $this->timestamp($offsetMs),
            $this->actorFields($playerGuid),
            $this->actorFields($playerGuid),
            $spellId,
            $spellName,
        ));
    }

    private function immunityRemoved(int $offsetMs, int $spellId, string $spellName, string $playerGuid = self::PLAYER_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_REMOVED,%s,%s,%d,"%s",0x2,BUFF',
            $this->timestamp($offsetMs),
            $this->actorFields($playerGuid),
            $this->actorFields($playerGuid),
            $spellId,
            $spellName,
        ));
    }

    private function zoneChange(int $offsetMs): BaseEvent
    {
        return $this->parse(sprintf('%s  ZONE_CHANGE,2661,"Cinderbrew Meadery",8', $this->timestamp($offsetMs)));
    }

    private function npcCastSuccess(int $offsetMs, int $spellId, string $spellName, string $casterGuid = self::CREATURE_GUID): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_CAST_SUCCESS,%s,%s,%d,"%s",%s,%s',
            $this->timestamp($offsetMs),
            $this->actorFields($casterGuid),
            $this->actorFields(self::PLAYER_GUID),
            $spellId,
            $spellName,
            $this->hexSchool(self::SCHOOL_SHADOW),
            $this->advancedFields($casterGuid),
        ));
    }

    private function npcDamage(
        int    $offsetMs,
        int    $spellId,
        string $spellName,
        int    $school = self::SCHOOL_SHADOW,
        int    $amount = 12345,
        string $destGuid = self::PLAYER_GUID,
        string $sourceGuid = self::CREATURE_GUID,
    ): BaseEvent {
        return $this->damage('SPELL_DAMAGE', $offsetMs, $spellId, $spellName, $school, $amount, $destGuid, $sourceGuid);
    }

    private function npcPeriodicDamage(int $offsetMs, int $spellId, string $spellName): BaseEvent
    {
        return $this->damage('SPELL_PERIODIC_DAMAGE', $offsetMs, $spellId, $spellName, self::SCHOOL_SHADOW, 12345, self::PLAYER_GUID, self::CREATURE_GUID);
    }

    private function damage(
        string $eventName,
        int    $offsetMs,
        int    $spellId,
        string $spellName,
        int    $school,
        int    $amount,
        string $destGuid,
        string $sourceGuid,
    ): BaseEvent {
        return $this->parse(sprintf(
            '%s  %s,%s,%s,%d,"%s",%s,%s,%d,%d,0,%d,0,0,0,nil,nil,nil,ST',
            $this->timestamp($offsetMs),
            $eventName,
            $this->actorFields($sourceGuid),
            $this->actorFields($destGuid),
            $spellId,
            $spellName,
            $this->hexSchool($school),
            $this->advancedFields($sourceGuid),
            $amount,
            $amount,
            $school,
        ));
    }

    private function npcDebuffApplied(int $offsetMs, int $spellId, string $spellName, int $school = self::SCHOOL_SHADOW): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_AURA_APPLIED,%s,%s,%d,"%s",%s,DEBUFF',
            $this->timestamp($offsetMs),
            $this->actorFields(self::CREATURE_GUID),
            $this->actorFields(self::PLAYER_GUID),
            $spellId,
            $spellName,
            $this->hexSchool($school),
        ));
    }

    private function npcMissed(int $offsetMs, int $spellId, string $spellName): BaseEvent
    {
        return $this->parse(sprintf(
            '%s  SPELL_MISSED,%s,%s,%d,"%s",%s,IMMUNE,nil,ST',
            $this->timestamp($offsetMs),
            $this->actorFields(self::CREATURE_GUID),
            $this->actorFields(self::PLAYER_GUID),
            $spellId,
            $spellName,
            $this->hexSchool(self::SCHOOL_SHADOW),
        ));
    }

    /**
     * The prefix school is logged as a hex string, unlike the damage suffix school which is a plain mask.
     */
    private function hexSchool(int $school): string
    {
        return sprintf('0x%x', $school);
    }

    /**
     * The 4 generic-data fields (guid, name, flags, raid flags) for one side of an event.
     */
    private function actorFields(string $guid): string
    {
        if (str_starts_with($guid, 'Player-')) {
            return sprintf('%s,"Jaxeek-TarrenMill-EU",0x511,0x80000000', $guid);
        }

        return sprintf('%s,"Dread Raven",0xa48,0x80000000', $guid);
    }

    /**
     * The 19 advanced-logging fields that a SPELL_DAMAGE carries on RETAIL_11_0_5.
     */
    private function advancedFields(string $infoGuid): string
    {
        return sprintf(
            '%s,0000000000000000,436040,436040,3094,437,859,100,413,35241,3,90,100,0,1238.22,1700.90,601,5.7883,287',
            $infoGuid,
        );
    }

    private function timestamp(int $offsetMs): string
    {
        return sprintf('%s-4', Carbon::create(2024, 8, 2, 16, 24, 18)->addMilliseconds($offsetMs)->format('n/j/Y H:i:s.v'));
    }

    private function parse(string $rawEvent): BaseEvent
    {
        return new CombatLogEntry($rawEvent)->parseEvent([], CombatLogVersion::RETAIL_11_0_5);
    }
}
