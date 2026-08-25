<?php

namespace Tests\Unit\App\Service\Spell\Tuning;

use App\Models\Spell\SpellTuningChangeType;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;
use App\Service\Spell\Tuning\Dtos\SpellTuningDiffResult;
use App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot;
use App\Service\Spell\Tuning\Logging\SpellTuningDiffServiceLoggingInterface;
use App\Service\Spell\Tuning\SpellTuningDiffService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('SpellTuning')]
final class SpellTuningDiffServiceTest extends PublicTestCase
{
    private const string FROM_BUILD = '12.1.0.69382';

    private const string TO_BUILD = '12.1.0.69404';

    private const int GAME_VERSION_ID = 1;

    private const int SPELL_ID = 1300877;

    private SpellTuningChangeRepositoryInterface&MockObject $repository;

    private SpellTuningDiffService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(SpellTuningChangeRepositoryInterface::class);
        $this->service    = new SpellTuningDiffService(
            $this->repository,
            $this->createMock(SpellTuningDiffServiceLoggingInterface::class),
        );
    }

    #[Test]
    public function diff_givenCoefficientChange_returnsValueChangedWithDelta(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0), $this->duration('10 sec')])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('38,793', 4.0), $this->duration('10 sec')])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $change = $result->changes[0];
        $this->assertSame(self::SPELL_ID, $change->spellId);
        $this->assertSame(SpellTuningChangeType::ValueChanged, $change->changeType);
        $this->assertSame(0, $change->valueIndex);
        $this->assertSame(SpellDescriptionValueKind::Damage, $change->kind);
        $this->assertSame(3.0, $change->oldCoefficient);
        $this->assertSame(4.0, $change->newCoefficient);
        $this->assertSame('29,095', $change->oldText);
        $this->assertSame('38,793', $change->newText);
        $this->assertEqualsWithDelta(1 / 3, $change->delta, 1e-9);
        $this->assertSame(1, $result->comparedSpellCount);
        $this->assertSame(1, $result->getChangedSpellCount());
        $this->assertSame(0, $result->getRewrittenCount());
    }

    #[Test]
    public function diff_givenFloatJitterOnCoefficient_returnsNoChange(): void
    {
        // Arrange - observed between two real builds: the same coefficient, differing in the 14th digit
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('305,070', 105.79386901856)])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('305,070', 105.79386901855)])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
    }

    #[Test]
    public function diff_givenMultiplierMeasuredForFirstTime_returnsNoChange(): void
    {
        // Arrange - the rendered number appears (calibration found a multiplier) but the coefficient did not move
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('', 3.0)])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0)])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
    }

    #[Test]
    public function diff_givenDurationTextChange_returnsValueChangedWithoutDelta(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0), $this->duration('10 sec')])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0), $this->duration('25 sec')])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $change = $result->changes[0];
        $this->assertSame(SpellTuningChangeType::ValueChanged, $change->changeType);
        $this->assertSame(1, $change->valueIndex);
        $this->assertSame(SpellDescriptionValueKind::Duration, $change->kind);
        $this->assertNull($change->oldCoefficient);
        $this->assertNull($change->newCoefficient);
        $this->assertSame('10 sec', $change->oldText);
        $this->assertSame('25 sec', $change->newText);
        $this->assertNull($change->delta);
    }

    #[Test]
    public function diff_givenDifferentKindSequence_returnsSingleDescriptionRewritten(): void
    {
        // Arrange - a value was added, so positions no longer line up
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(
            format: 'Deals %1$s damage.',
            values: [$this->damage('29,095', 3.0)],
        )]);
        $to = $this->snapshot(self::TO_BUILD, [$this->spell(
            format: 'Deals %1$s damage over %2$s.',
            values: [$this->damage('38,793', 4.0), $this->duration('6 sec')],
        )]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $change = $result->changes[0];
        $this->assertSame(SpellTuningChangeType::DescriptionRewritten, $change->changeType);
        $this->assertNull($change->valueIndex);
        $this->assertNull($change->kind);
        $this->assertSame('Deals 29,095 damage.', $change->oldText);
        $this->assertSame('Deals 38,793 damage over 6 sec.', $change->newText);
        $this->assertNull($change->delta);
        $this->assertSame(1, $result->getRewrittenCount());
    }

    #[Test]
    public function diff_givenDescriptionAdded_returnsDescriptionRewritten(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: null, values: [])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: 'Stuns for %1$s.', values: [$this->duration('3 sec')])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $this->assertSame(SpellTuningChangeType::DescriptionRewritten, $result->changes[0]->changeType);
        $this->assertNull($result->changes[0]->oldText);
        $this->assertSame('Stuns for 3 sec.', $result->changes[0]->newText);
    }

    #[Test]
    public function diff_givenPlaceholderSpellGainsDescription_returnsNoChange(): void
    {
        // Arrange - spell 1317558: a generic template Blizzard never fetched real data for (no icon, no
        // numbers, no dispel type at all), describing itself for the first time; not something a player
        // would recognise as a tuning change
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: null, values: [], iconName: '', dispelType: '')]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: 'Attack for Physical damage.', values: [], iconName: '', dispelType: '')]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
    }

    #[Test]
    public function diff_givenPlaceholderSpellRewordedOnBothSides_returnsNoChange(): void
    {
        // Arrange - still no icon, no numbers, no dispel type, so still noise even though both sides
        // describe it
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: 'Attack for Physical damage.', values: [], iconName: '', dispelType: '')]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: 'Attack.', values: [], iconName: '', dispelType: '')]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
    }

    #[Test]
    public function diff_givenRealSpellWithNoNumbersButAnIcon_returnsDescriptionRewritten(): void
    {
        // Arrange - a real spell can legitimately have static-text-only description; an icon is
        // enough to tell it apart from the placeholder template
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: null, values: [], iconName: 'ability_warrior_charge')]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: 'Charges the target.', values: [], iconName: 'ability_warrior_charge')]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $this->assertSame(SpellTuningChangeType::DescriptionRewritten, $result->changes[0]->changeType);
    }

    #[Test]
    public function diff_givenRealSpellWithNoNumbersNoIconButAGenuineDispelType_returnsDescriptionRewritten(): void
    {
        // Arrange - spells 153954 and 246943: real boss abilities with no icon and a purely static
        // description, same shape as the placeholder except their dispel_type was actually fetched
        // ("n/a" is itself real, fetched data) - must still be reported
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: null, values: [], iconName: '', dispelType: 'spelldispeltype.n_a')]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(
            format: 'High Sage Viryx summons a Solar Zealot above a random player.',
            values: [],
            iconName: '',
            dispelType: 'spelldispeltype.n_a',
        )]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $this->assertSame(SpellTuningChangeType::DescriptionRewritten, $result->changes[0]->changeType);
    }

    #[Test]
    public function diff_givenNoDescriptionOnEitherSide_returnsNoChange(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: null, values: [])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: null, values: [])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
        $this->assertSame(1, $result->comparedSpellCount);
    }

    #[Test]
    public function diff_givenRewordedFormatWithSameValues_returnsNoChange(): void
    {
        // Arrange - wording changed, numbers did not: not a tuning change
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(format: 'Inflicts %1$s Fire damage.', values: [$this->damage('29,095', 3.0)])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(format: 'Deals %1$s Fire damage to the target.', values: [$this->damage('29,095', 3.0)])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
    }

    #[Test]
    public function diff_givenSpellOnlyInOneSnapshot_ignoresIt(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(id: 1, values: [$this->damage('29,095', 3.0)])]);
        $to   = $this->snapshot(self::TO_BUILD, [
            $this->spell(id: 1, values: [$this->damage('29,095', 3.0)]),
            $this->spell(id: 2, values: [$this->damage('29,095', 3.0)]),
        ]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertSame([], $result->changes);
        $this->assertSame(1, $result->comparedSpellCount);
    }

    #[Test]
    public function diff_givenOldCoefficientZero_returnsNullDelta(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('', 0.0)])]);
        $to   = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('9,698', 1.0)])]);

        // Act
        $result = $this->service->diff($from, $to);

        // Assert
        $this->assertCount(1, $result->changes);
        $this->assertNull($result->changes[0]->delta);
        $this->assertSame(0.0, $result->changes[0]->oldCoefficient);
        $this->assertSame(1.0, $result->changes[0]->newCoefficient);
    }

    #[Test]
    public function diff_givenDifferentGameVersions_throwsInvalidArgumentException(): void
    {
        // Arrange
        $from = $this->snapshot(self::FROM_BUILD, [], gameVersionId: 1);
        $to   = $this->snapshot(self::TO_BUILD, [], gameVersionId: 2);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->service->diff($from, $to);
    }

    #[Test]
    public function store_givenResult_replacesRowsForTargetBuild(): void
    {
        // Arrange
        $from   = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0)])]);
        $to     = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('38,793', 4.0)])]);
        $result = $this->service->diff($from, $to);

        $this->repository->expects($this->once())
            ->method('replaceForBuild')
            ->with(
                self::GAME_VERSION_ID,
                self::TO_BUILD,
                $this->callback(static function (array $rows): bool {
                    return count($rows) === 1
                        && $rows[0]['spell_id'] === self::SPELL_ID
                        && $rows[0]['from_build'] === self::FROM_BUILD
                        && $rows[0]['to_build'] === self::TO_BUILD
                        && $rows[0]['to_build_number'] === 69404
                        && $rows[0]['change_type'] === 'value_changed'
                        && $rows[0]['kind'] === 'damage';
                }),
            )
            ->willReturn(1);

        // Act
        $stored = $this->service->store($result);

        // Assert
        $this->assertSame(1, $stored);
    }

    #[Test]
    public function toRows_givenResult_returnsRowsWithoutObjects(): void
    {
        // Arrange - what the repository inserts must be plain scalars (enum values, not enums)
        $from   = $this->snapshot(self::FROM_BUILD, [$this->spell(values: [$this->damage('29,095', 3.0)])]);
        $to     = $this->snapshot(self::TO_BUILD, [$this->spell(values: [$this->damage('38,793', 4.0)])]);
        $result = $this->service->diff($from, $to);

        // Act
        $rows = $result->toRows();

        // Assert
        $this->assertInstanceOf(SpellTuningDiffResult::class, $result);
        foreach ($rows[0] as $column => $value) {
            $this->assertTrue(is_scalar($value) || $value === null, sprintf('Column %s is not a scalar', $column));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $spells
     */
    private function snapshot(string $build, array $spells, int $gameVersionId = self::GAME_VERSION_ID): SpellTuningSnapshot
    {
        return SpellTuningSnapshot::fromSpellArrays($build, $gameVersionId, $spells);
    }

    /**
     * @param  array<int, array<string, mixed>> $values
     * @return array<string, mixed>
     */
    private function spell(
        array   $values,
        int     $id = self::SPELL_ID,
        ?string $format = 'Deals %1$s Shadow damage over %2$s.',
        string  $iconName = 'spell_icon',
        string  $dispelType = 'spelldispeltype.n_a',
    ): array {
        return [
            'id'                 => $id,
            'game_version_id'    => self::GAME_VERSION_ID,
            'description_format' => $format,
            'description_values' => $values,
            'icon_name'          => $iconName,
            'dispel_type'        => $dispelType,
        ];
    }

    /** @return array<string, mixed> */
    private function damage(string $text, float $coefficient): array
    {
        return ['kind' => 'damage', 'text' => $text, 'spellId' => self::SPELL_ID, 'coefficient' => $coefficient, 'effectIndex' => 0];
    }

    /** @return array<string, mixed> */
    private function duration(string $text): array
    {
        return ['kind' => 'duration', 'text' => $text];
    }
}
