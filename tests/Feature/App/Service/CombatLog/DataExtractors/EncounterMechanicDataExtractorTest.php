<?php

namespace Tests\Feature\App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Models\Dungeon;
use App\Repositories\Database\CombatLog\CombatLogEncounterObservationRepository;
use App\Service\CombatLog\DataExtractors\EncounterMechanicDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('EncounterMechanics')]
final class EncounterMechanicDataExtractorTest extends PublicTestCase
{
    private const string COMBAT_LOG_PATH = '/tmp/run_9995810_segment_4.txt';

    private const int DUNGEON_ID = 9_995_810;

    private const int AFFIX_GROUP_ID = 9_995_811;

    private const int ENCOUNTER_ID = 3208;

    private const int BOSS_NPC_ID = 244100;

    private const int ADD_NPC_ID = 244759;

    private const string BOSS_GUID = 'Creature-0-1467-2825-72653-244100-000001B61C';

    private const string PLAYER_GUID = 'Player-1615-0B7B4553';

    private const string PET_GUID = 'Pet-0-1467-2825-72653-165189-020322FD3B';

    private const string FLAGS_PLAYER   = '0x512';
    private const string FLAGS_PET      = '0x1111';
    private const string FLAGS_GUARDIAN = '0x2111';
    private const string FLAGS_HOSTILE  = '0xa48';
    private const string FLAGS_NEUTRAL  = '0xa28';
    private const string FLAGS_FRIENDLY = '0xa18';

    private const int BOSS_MAX_HP = 83_262_462;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanUpTestData();
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
    public function afterExtract_givenAKilledEncounter_writesOneObservationWithTheEncounterContext(): void
    {
        // Arrange
        $extractor = $this->createExtractor();

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->playerDamage(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterEnd(170_340, true),
        ]);

        // Assert
        $observation = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole();
        $this->assertSame(self::DUNGEON_ID, $observation->dungeon_id);
        $this->assertSame(self::ENCOUNTER_ID, $observation->encounter_id);
        $this->assertSame('run:9995810', $observation->run_key);
        $this->assertSame(16, $observation->key_level);
        $this->assertSame(self::AFFIX_GROUP_ID, $observation->affix_group_id);
        $this->assertTrue($observation->success);
        $this->assertSame(170_340, $observation->duration_ms);
        $this->assertSame([self::BOSS_NPC_ID], array_keys($observation->data['npcs']));
    }

    #[Test]
    public function afterExtract_givenAWipe_writesTheObservationAsUnsuccessful(): void
    {
        // Arrange
        $extractor = $this->createExtractor();

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->playerDamage(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterEnd(60_000, false),
        ]);

        // Assert
        $observation = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole();
        $this->assertFalse($observation->success);
        $this->assertSame(60_000, $observation->duration_ms);
    }

    #[Test]
    public function afterExtract_givenAnEncounterThatNeverEnds_dropsItButKeepsTheNextAttempt(): void
    {
        // Arrange - the first attempt is cut off by a second ENCOUNTER_START, the second one never ends at all
        $extractor = $this->createExtractor();

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0, 3207),
            $this->playerDamage(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterStart(10_000),
            $this->playerDamage(11_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterEnd(20_000, true),
            $this->encounterStart(30_000, 3209),
            $this->playerDamage(31_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
        ]);

        // Assert
        $observations = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->get();
        $this->assertSame([self::ENCOUNTER_ID], $observations->pluck('encounter_id')->all());
        $observation = $observations->first();
        $this->assertSame(10_000, $observation->duration_ms);
        $this->assertSame(1, $observation->data['npcs'][self::BOSS_NPC_ID]['player_damage_events']);
    }

    #[Test]
    public function beforeExtract_givenAnEncounterLeftOpenByTheEarlierFile_doesNotCarryItOver(): void
    {
        // Arrange - the earlier file opens the encounter but does not end it
        $extractor = $this->createExtractor();
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->playerDamage(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
        ], '/tmp/run_9995810_segment_3.txt');

        // Act - the next file carries the ENCOUNTER_END, and a complete attempt as a positive control
        $this->extract($extractor, [
            $this->playerDamage(2_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterEnd(3_000, true),
            $this->encounterStart(10_000, 3209),
            $this->encounterEnd(20_000, true, 3209),
        ]);

        // Assert
        $observations = CombatLogEncounterObservation::query()->where('combat_log_path', 'like', '/tmp/run_9995810_segment_%')->get();
        $this->assertSame([3209], $observations->pluck('encounter_id')->all());
    }

    #[Test]
    public function extractData_givenUnitsSeenOnlyOutsideTheEncounter_leavesThemOut(): void
    {
        // Arrange
        $extractor = $this->createExtractor();
        $trashGuid = $this->creatureGuid(999_001, 1);

        // Act
        $this->extract($extractor, [
            $this->playerDamage(0, $trashGuid, self::FLAGS_HOSTILE, 1_000, 1_000),
            $this->unitDied(500, $trashGuid),
            $this->encounterStart(1_000),
            $this->playerDamage(2_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_MAX_HP, self::BOSS_MAX_HP),
            $this->encounterEnd(3_000, true),
            $this->playerDamage(4_000, $trashGuid, self::FLAGS_HOSTILE, 1_000, 1_000),
        ]);

        // Assert
        $observation = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole();
        $this->assertSame([self::BOSS_NPC_ID], array_keys($observation->data['npcs']));
        $this->assertSame(1, $observation->data['npcs'][self::BOSS_NPC_ID]['player_damage_events']);
    }

    #[Test]
    public function afterExtract_givenUnitsOfAnNpc_writesItsReactionHealthCountsAndSummoner(): void
    {
        // Arrange - two neutral adds the boss summons; players, a pet and a guardian hit them, which counts; the boss and a SWING_DAMAGE_LANDED do not
        $extractor     = $this->createExtractor();
        $addGuid1      = $this->creatureGuid(self::ADD_NPC_ID, 1);
        $addGuid2      = $this->creatureGuid(self::ADD_NPC_ID, 2);
        $guardianGuid  = $this->creatureGuid(260_463, 3);
        $friendlyGuid  = $this->creatureGuid(246_409, 4);
        $addMaxHp      = 2_643_254;
        $addMaxHpEarly = 2_000_000;

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->summon(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid1, self::FLAGS_NEUTRAL),
            $this->summon(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid2, self::FLAGS_NEUTRAL),
            $this->castSuccess(1_500, $friendlyGuid, self::FLAGS_FRIENDLY, 5_000, 5_000),
            $this->playerDamage(2_000, $addGuid1, self::FLAGS_HOSTILE, $addMaxHpEarly, $addMaxHpEarly),
            $this->playerDamage(3_000, $addGuid1, self::FLAGS_HOSTILE, 1_321_627, $addMaxHp),
            $this->damage(3_500, self::PET_GUID, self::FLAGS_PET, $addGuid1, self::FLAGS_HOSTILE, 1_000_000, $addMaxHp),
            $this->damage(3_600, $guardianGuid, self::FLAGS_GUARDIAN, $addGuid2, self::FLAGS_HOSTILE, 2_000_000, $addMaxHpEarly),
            $this->damage(3_700, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid2, self::FLAGS_HOSTILE, 1_900_000, $addMaxHpEarly),
            $this->damage(3_800, self::PLAYER_GUID, self::FLAGS_PLAYER, $addGuid2, self::FLAGS_HOSTILE, 1_800_000, $addMaxHpEarly, 'SWING_DAMAGE_LANDED'),
            $this->partyKill(9_000, $addGuid1),
            $this->unitDied(9_000, $addGuid1),
            $this->encounterEnd(20_000, true),
        ]);

        // Assert
        $npcs = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['npcs'];
        $add  = $npcs[self::ADD_NPC_ID];
        $this->assertSame('neutral', $add['reaction']);
        $this->assertSame($addMaxHp, $add['max_hp']);
        $this->assertSame(2, $add['count']);
        $this->assertSame(4, $add['player_damage_events']);
        $this->assertSame(1, $add['deaths']);
        $this->assertSame(self::BOSS_NPC_ID, $add['summoned_by_npc_id']);
        $this->assertEquals(37.8, $add['min_hp_pct']);
        $this->assertSame([8_000], $add['lifetimes_ms']);
        $this->assertSame('hostile', $npcs[self::BOSS_NPC_ID]['reaction']);
        $this->assertNull($npcs[self::BOSS_NPC_ID]['max_hp']);
        $this->assertSame('friendly', $npcs[246_409]['reaction']);
        $this->assertSame(0, $npcs[246_409]['player_damage_events']);
    }

    #[Test]
    public function afterExtract_givenUnitsFirstSeenAtDifferentTimes_groupsThemIntoWavesWithTheBossHealth(): void
    {
        // Arrange - two adds 2.5 s apart form one wave, a third 10 s later forms the next
        $extractor = $this->createExtractor();
        $addGuid1  = $this->creatureGuid(self::ADD_NPC_ID, 1);
        $addGuid2  = $this->creatureGuid(self::ADD_NPC_ID, 2);
        $addGuid3  = $this->creatureGuid(self::ADD_NPC_ID, 3);

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->playerDamage(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 80_000_000, 100_000_000),
            $this->summon(29_500, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid1, self::FLAGS_NEUTRAL),
            $this->summon(32_000, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid2, self::FLAGS_NEUTRAL),
            $this->playerDamage(35_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 45_540_000, 100_000_000),
            $this->summon(42_000, self::BOSS_GUID, self::FLAGS_HOSTILE, $addGuid3, self::FLAGS_NEUTRAL),
            $this->encounterEnd(60_000, true),
        ]);

        // Assert
        $waves = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['npcs'][self::ADD_NPC_ID]['waves'];
        $this->assertEquals([
            ['t' => 29_500, 'count' => 2, 'boss_hp_pct' => 80.0],
            ['t' => 42_000, 'count' => 1, 'boss_hp_pct' => 45.5],
        ], $waves);
    }

    #[Test]
    public function afterExtract_givenAMuchAttackedUnitTakingNoDamageForFiveSeconds_writesTheWindowWithTheAurasAroundItsStart(): void
    {
        // Arrange - 100 hits a second apart, then the boss gains an aura and takes nothing for 6 s; a player debuff
        // and an aura long before the gap are not reported, and a 4 s gap is not a window
        $extractor = $this->createExtractor();
        $events    = [$this->encounterStart(0)];
        for ($second = 1; $second <= 100; $second++) {
            $events[] = $this->playerDamage($second * 1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 80_000_000, 100_000_000);
        }

        $events[] = $this->auraApplied(50_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_GUID, 1_298_220);
        $events[] = $this->auraApplied(101_000, self::BOSS_GUID, self::FLAGS_HOSTILE, self::BOSS_GUID, 1_298_221);
        $events[] = $this->auraApplied(101_500, 'Vehicle-0-1467-2825-72653-259999-000001B61D', self::FLAGS_HOSTILE, self::BOSS_GUID, 1_298_223);
        $events[] = $this->auraApplied(101_500, self::PLAYER_GUID, self::FLAGS_PLAYER, self::BOSS_GUID, 589);
        $events[] = $this->playerDamage(106_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 70_000_000, 100_000_000);
        $events[] = $this->playerDamage(110_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 60_000_000, 100_000_000);
        $events[] = $this->encounterEnd(120_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $noDamageWindows = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['no_damage_windows'];
        $this->assertEquals([
            [
                'npc_id'         => self::BOSS_NPC_ID,
                't'              => 100_000,
                'duration_ms'    => 6_000,
                'hp_pct'         => 80.0,
                'aura_spell_ids' => [1_298_221, 1_298_223],
            ],
        ], $noDamageWindows);
    }

    #[Test]
    public function afterExtract_givenALittleAttackedUnitTakingNoDamageForFiveSeconds_writesNoWindow(): void
    {
        // Arrange - the same 6 s gap on a unit that took only 99 player hits, next to the boss with 100 as control
        $extractor = $this->createExtractor();
        $addGuid   = $this->creatureGuid(self::ADD_NPC_ID, 1);
        $events    = [$this->encounterStart(0)];
        for ($second = 1; $second <= 98; $second++) {
            $events[] = $this->playerDamage($second * 1_000, $addGuid, self::FLAGS_HOSTILE, 1_000, 2_000);
            $events[] = $this->playerDamage($second * 1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 1_000, 2_000);
        }

        $events[] = $this->playerDamage(104_000, $addGuid, self::FLAGS_HOSTILE, 1_000, 2_000);
        $events[] = $this->playerDamage(104_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 1_000, 2_000);
        $events[] = $this->playerDamage(105_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 1_000, 2_000);
        $events[] = $this->encounterEnd(120_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $data = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data;
        $this->assertSame(99, $data['npcs'][self::ADD_NPC_ID]['player_damage_events']);
        $this->assertSame([self::BOSS_NPC_ID], array_column($data['no_damage_windows'], 'npc_id'));
    }

    #[Test]
    public function afterExtract_givenUnitsWhoseHealthMovesInLockstep_writesThemAsASharedHealthGroup(): void
    {
        // Arrange - three bosses with one max HP lose health together; a fourth with that max HP and a fifth with
        // another max HP that also moves along both fall outside the group
        $extractor   = $this->createExtractor();
        $sharedGuids = [
            $this->creatureGuid(243_028, 1),
            $this->creatureGuid(243_029, 2),
            $this->creatureGuid(243_030, 3),
        ];
        $divergentGuid = $this->creatureGuid(243_031, 4);
        $otherMaxGuid  = $this->creatureGuid(243_032, 5);
        $events        = [$this->encounterStart(0)];
        for ($second = 0; $second < 12; $second++) {
            $hpPct = 100 - $second * 5;
            foreach ($sharedGuids as $index => $guid) {
                $events[] = $this->playerDamage($second * 1_000 + 100, $guid, self::FLAGS_HOSTILE, ($hpPct - $index) * 10_000, 1_000_000);
            }

            $events[] = $this->playerDamage($second * 1_000 + 100, $divergentGuid, self::FLAGS_HOSTILE, ($hpPct - ($second % 2) * 10) * 10_000, 1_000_000);
            $events[] = $this->playerDamage($second * 1_000 + 100, $otherMaxGuid, self::FLAGS_HOSTILE, $hpPct * 20_000, 2_000_000);
        }

        $events[] = $this->encounterEnd(20_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $sharedHealth = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['shared_health'];
        $this->assertSame([[243_028, 243_029, 243_030]], $sharedHealth);
    }

    #[Test]
    public function afterExtract_givenUnitsInLockstepThatStayAboveSeventyPercent_writesNoSharedHealthGroup(): void
    {
        // Arrange - the same lockstep, ending at 71%
        $extractor = $this->createExtractor();
        $guids     = [$this->creatureGuid(243_028, 1), $this->creatureGuid(243_029, 2)];
        $events    = [$this->encounterStart(0)];
        for ($second = 0; $second < 12; $second++) {
            foreach ($guids as $guid) {
                $events[] = $this->playerDamage($second * 1_000 + 100, $guid, self::FLAGS_HOSTILE, (82 - $second) * 10_000, 1_000_000);
            }
        }

        $events[] = $this->encounterEnd(20_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $data = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data;
        $this->assertEquals(71.0, $data['npcs'][243_028]['min_hp_pct']);
        $this->assertSame([], $data['shared_health']);
    }

    #[Test]
    public function afterExtract_givenPlayerOwnedUnits_leavesThemOut(): void
    {
        // Arrange - a player's summon, a unit owned by a player, and a guardian known only by its flags
        $extractor    = $this->createExtractor();
        $summonedGuid = $this->creatureGuid(260_463, 1);
        $ownedGuid    = $this->creatureGuid(234_018, 2);
        $flaggedGuid  = $this->creatureGuid(234_019, 3);

        // Act
        $this->extract($extractor, [
            $this->encounterStart(0),
            $this->summon(500, self::PLAYER_GUID, self::FLAGS_PLAYER, $summonedGuid, self::FLAGS_NEUTRAL),
            $this->castSuccess(600, $ownedGuid, self::FLAGS_NEUTRAL, 100, 100, self::PLAYER_GUID),
            $this->damage(700, $flaggedGuid, self::FLAGS_GUARDIAN, self::BOSS_GUID, self::FLAGS_HOSTILE, 99, 100),
            $this->playerDamage(800, self::BOSS_GUID, self::FLAGS_HOSTILE, 98, 100),
            $this->encounterEnd(20_000, true),
        ]);

        // Assert
        $npcs = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['npcs'];
        $this->assertSame([self::BOSS_NPC_ID], array_keys($npcs));
        $this->assertSame(2, $npcs[self::BOSS_NPC_ID]['player_damage_events']);
    }

    #[Test]
    public function afterExtract_givenAllPlayersButOneDisplacedTwelveYardsInThreeSeconds_writesAMovementWindowWithTheCastsAroundItsStart(): void
    {
        // Arrange - four players walk 15 yd from second 5 to 8, the fifth stands still; casts by the boss inside
        // the 4 s before to 1 s after second 5 are reported, the friendly NPC's, the player's summon's and the late one not
        $extractor        = $this->createExtractor();
        $friendlyGuid     = $this->creatureGuid(246_409, 1);
        $playerSummonGuid = $this->creatureGuid(260_463, 2);
        $events           = [
            $this->encounterStart(0),
            $this->cast(900, self::BOSS_GUID, self::FLAGS_HOSTILE, 101),
            $this->cast(1_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 102),
            $this->cast(3_000, $friendlyGuid, self::FLAGS_FRIENDLY, 103),
            $this->summon(3_400, self::PLAYER_GUID, self::FLAGS_PLAYER, $playerSummonGuid, self::FLAGS_NEUTRAL),
            $this->cast(3_500, $playerSummonGuid, self::FLAGS_NEUTRAL, 104),
            $this->cast(6_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 105),
            $this->cast(6_000, self::BOSS_GUID, self::FLAGS_HOSTILE, 102),
            $this->cast(6_001, self::BOSS_GUID, self::FLAGS_HOSTILE, 106),
        ];
        for ($second = 0; $second <= 10; $second++) {
            for ($player = 1; $player <= 5; $player++) {
                $x        = $player === 5 ? 0.0 : 5.0 * max(0, min(3, $second - 5));
                $events[] = $this->playerPosition($second * 1_000 + 100, $player, $x, 0.0);
            }
        }

        $events[] = $this->encounterEnd(20_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $movementWindows = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['movement_windows'];
        $this->assertEquals([
            [
                't'               => 5_000,
                'duration_ms'     => 3_000,
                'players'         => 5,
                'median_distance' => 15.0,
                'cast_spell_ids'  => [102, 105],
            ],
        ], $movementWindows);
    }

    #[Test]
    public function afterExtract_givenTwoPlayersStandingStill_writesNoMovementWindow(): void
    {
        // Arrange - at second 5 three of five players move, at second 20 four of them do
        $extractor = $this->createExtractor();
        $events    = [$this->encounterStart(0)];
        for ($second = 0; $second <= 25; $second++) {
            for ($player = 1; $player <= 5; $player++) {
                $x = match ($player) {
                    4       => 0.0,
                    5       => 5.0 * max(0, min(3, $second - 20)),
                    default => 5.0 * max(0, min(3, $second - 5)) + 5.0 * max(0, min(3, $second - 20)),
                };

                $events[] = $this->playerPosition($second * 1_000 + 100, $player, $x, 0.0);
            }
        }

        $events[] = $this->encounterEnd(30_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $movementWindows = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['movement_windows'];
        $this->assertSame([20_000], array_column($movementWindows, 't'));
    }

    #[Test]
    public function afterExtract_givenFewerThanFourPlayersOnOneUiMapAtBothEnds_writesNoMovementWindow(): void
    {
        // Arrange - four players move at second 5 but one of them changes ui map on the way; at second 20 all four
        // move on one map
        $extractor = $this->createExtractor();
        $events    = [$this->encounterStart(0)];
        for ($second = 0; $second <= 25; $second++) {
            for ($player = 1; $player <= 4; $player++) {
                $x        = 5.0 * max(0, min(3, $second - 5)) + 5.0 * max(0, min(3, $second - 20));
                $uiMapId  = $player === 4 && $second >= 8 && $second < 15 ? 2515 : 2514;
                $events[] = $this->playerPosition($second * 1_000 + 100, $player, $x, 0.0, $uiMapId);
            }
        }

        $events[] = $this->encounterEnd(30_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $movementWindows = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['movement_windows'];
        $this->assertSame([20_000], array_column($movementWindows, 't'));
    }

    #[Test]
    public function afterExtract_givenMovingSecondsAtMostTwoSecondsApart_mergesThemIntoOneWindow(): void
    {
        // Arrange - positions only at seconds 5, 7, 8, 10 and 13: the party moves over 5-8, 7-10 and 10-13
        $extractor = $this->createExtractor();
        $events    = [$this->encounterStart(0)];
        foreach ([5 => 0.0, 7 => 0.0, 8 => 15.0, 10 => 15.0, 13 => 30.0] as $second => $x) {
            for ($player = 1; $player <= 4; $player++) {
                $events[] = $this->playerPosition($second * 1_000 + 100, $player, $x, 0.0);
            }
        }

        $events[] = $this->encounterEnd(30_000, true);

        // Act
        $this->extract($extractor, $events);

        // Assert
        $movementWindows = CombatLogEncounterObservation::query()->where('combat_log_path', self::COMBAT_LOG_PATH)->sole()->data['movement_windows'];
        $this->assertSame([[5_000, 5_000], [10_000, 3_000]], array_map(
            static fn(array $movementWindow) => [$movementWindow['t'], $movementWindow['duration_ms']],
            $movementWindows,
        ));
    }

    /**
     * @param array<int, BaseEvent|null> $events
     */
    private function extract(
        EncounterMechanicDataExtractor $extractor,
        array                          $events,
        string                         $combatLogFilePath = self::COMBAT_LOG_PATH,
    ): void {
        $dungeon     = new Dungeon();
        $dungeon->id = self::DUNGEON_ID;

        $affixGroup     = new AffixGroup();
        $affixGroup->id = self::AFFIX_GROUP_ID;

        $currentDungeon = new DataExtractionCurrentDungeon($dungeon, 16, $affixGroup);

        $result = new ExtractedDataResult();
        $extractor->beforeExtract($result, $combatLogFilePath);
        foreach ($events as $event) {
            $this->assertNotNull($event);
            $extractor->extractData($result, $currentDungeon, $event);
        }

        $extractor->afterExtract($result, $combatLogFilePath);
    }

    private function createExtractor(): EncounterMechanicDataExtractor
    {
        return new EncounterMechanicDataExtractor(new CombatLogEncounterObservationRepository());
    }

    private function creatureGuid(int $npcId, int $spawn): string
    {
        return sprintf('Creature-0-1467-2825-72653-%d-%010X', $npcId, $spawn);
    }

    private function encounterStart(int $offsetMs, int $encounterId = self::ENCOUNTER_ID): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf('ENCOUNTER_START,%d,"Sentinel of Winter",8,5,2825', $encounterId));
    }

    private function encounterEnd(int $offsetMs, bool $success, int $encounterId = self::ENCOUNTER_ID): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf('ENCOUNTER_END,%d,"Sentinel of Winter",8,5,%d,%d', $encounterId, $success ? 1 : 0, $offsetMs));
    }

    private function playerDamage(int $offsetMs, string $destGuid, string $destFlags, int $currentHp, int $maxHp): ?BaseEvent
    {
        return $this->damage($offsetMs, self::PLAYER_GUID, self::FLAGS_PLAYER, $destGuid, $destFlags, $currentHp, $maxHp);
    }

    /**
     * A damage event whose advanced data describes the target.
     */
    private function damage(
        int    $offsetMs,
        string $sourceGuid,
        string $sourceFlags,
        string $destGuid,
        string $destFlags,
        int    $currentHp,
        int    $maxHp,
        string $eventName = 'SPELL_DAMAGE',
    ): ?BaseEvent {
        $spell = $eventName === 'SWING_DAMAGE_LANDED' ? '' : '1242035,"Worldsoul Aegis",0x8,';

        return $this->parse($offsetMs, sprintf(
            '%s,%s,"Source",%s,0x80000000,%s,"Target",%s,0x80000000,%s%s,0000000000000000,%d,%d,0,0,1470,0,0,0,0,9916,9916,0,4593.88,-5211.15,2514,0.1991,90,3660,1829,-1,8,0,0,0,1,nil,nil%s',
            $eventName,
            $sourceGuid,
            $sourceFlags,
            $destGuid,
            $destFlags,
            $spell,
            $destGuid,
            $currentHp,
            $maxHp,
            $eventName === 'SWING_DAMAGE_LANDED' ? '' : ',AOE',
        ));
    }

    /**
     * A cast whose advanced data describes the caster.
     */
    private function castSuccess(
        int    $offsetMs,
        string $sourceGuid,
        string $sourceFlags,
        int    $currentHp,
        int    $maxHp,
        string $ownerGuid = '0000000000000000',
    ): ?BaseEvent {
        return $this->parse($offsetMs, sprintf(
            'SPELL_CAST_SUCCESS,%s,"Caster",%s,0x80000000,0000000000000000,nil,0x80000000,0x80000000,1235548,"Glacial Torment",0x1,%s,%s,%d,%d,0,0,1470,0,0,0,3,21,100,0,4578.53,-5212.16,2514,4.2080,92',
            $sourceGuid,
            $sourceFlags,
            $sourceGuid,
            $ownerGuid,
            $currentHp,
            $maxHp,
        ));
    }

    /**
     * A cast by a player whose advanced data places them at in-game (x, y).
     */
    private function playerPosition(int $offsetMs, int $player, float $x, float $y, int $uiMapId = 2514): ?BaseEvent
    {
        $playerGuid = sprintf('Player-1615-%08X', $player);

        // The log stores in-game y first and -x second
        return $this->parse($offsetMs, sprintf(
            'SPELL_CAST_SUCCESS,%s,"Player",0x512,0x80000000,0000000000000000,nil,0x80000000,0x80000000,31935,"Avenger\'s Shield",0x2,%s,0000000000000000,100,100,0,0,1470,0,0,0,0,100,100,0,%.2f,%.2f,%d,0.1991,90',
            $playerGuid,
            $playerGuid,
            $y,
            -$x,
            $uiMapId,
        ));
    }

    private function cast(int $offsetMs, string $sourceGuid, string $sourceFlags, int $spellId): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf(
            'SPELL_CAST_START,%s,"Caster",%s,0x80000000,0000000000000000,nil,0x80000000,0x80000000,%d,"Spell",0x1',
            $sourceGuid,
            $sourceFlags,
            $spellId,
        ));
    }

    private function auraApplied(int $offsetMs, string $sourceGuid, string $sourceFlags, string $destGuid, int $spellId): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf(
            'SPELL_AURA_APPLIED,%s,"Caster",%s,0x80000000,%s,"Target",0xa48,0x80000000,%d,"Aura",0x1,BUFF',
            $sourceGuid,
            $sourceFlags,
            $destGuid,
            $spellId,
        ));
    }

    private function summon(int $offsetMs, string $sourceGuid, string $sourceFlags, string $destGuid, string $destFlags): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf(
            'SPELL_SUMMON,%s,"Summoner",%s,0x80000000,%s,"Summon",%s,0x80000000,1235635,"Summon",0x10',
            $sourceGuid,
            $sourceFlags,
            $destGuid,
            $destFlags,
        ));
    }

    private function unitDied(int $offsetMs, string $destGuid): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf('UNIT_DIED,0000000000000000,nil,0x80000000,0x80000000,%s,"Target",0xa48,0x80000000,0', $destGuid));
    }

    private function partyKill(int $offsetMs, string $destGuid): ?BaseEvent
    {
        return $this->parse($offsetMs, sprintf('PARTY_KILL,%s,"Player",0x512,0x80000000,%s,"Target",0xa48,0x80000000,0', self::PLAYER_GUID, $destGuid));
    }

    private function parse(int $offsetMs, string $eventData): ?BaseEvent
    {
        $timestamp = Carbon::create(2026, 11, 21, 17, 17, 53)->addMilliseconds($offsetMs)->format('m/d/Y H:i:s.v');

        return new CombatLogEntry(sprintf('%s2  %s', $timestamp, $eventData))->parseEvent();
    }

    private function cleanUpTestData(): void
    {
        CombatLogEncounterObservation::query()->where('combat_log_path', 'like', '/tmp/run_9995810_segment_%')->delete();
    }
}
