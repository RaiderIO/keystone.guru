<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellBuilding;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellPeriodic;
use App\Logic\CombatLog\CombatEvents\Prefixes\Swing;
use App\Logic\CombatLog\CombatEvents\Prefixes\SwingDamageLandedSupport;
use App\Logic\CombatLog\CombatLogVersion;
use Exception;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('Prefix')]
final class PrefixTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('createFromEventName_givenAnEventName_returnsTheMostSpecificPrefix_DataProvider')]
    public function createFromEventName_givenAnEventName_returnsTheMostSpecificPrefix(
        string $eventName,
        string $expectedClassName,
    ): void {
        // Arrange
        // Act
        $prefix = Prefix::createFromEventName(CombatLogVersion::RETAIL_11_0_5, $eventName);

        // Assert
        $this->assertSame($expectedClassName, $prefix::class);
    }

    /**
     * @return array<string, array{0: string, 1: class-string<Prefix>}>
     */
    public static function createFromEventName_givenAnEventName_returnsTheMostSpecificPrefix_DataProvider(): array
    {
        return [
            'swing'        => ['SWING_DAMAGE', Swing::class],
            'swing missed' => ['SWING_MISSED', Swing::class],
            // The one entry that must outrank its own SWING prefix - it is a spell, not a swing
            'swing damage landed support' => ['SWING_DAMAGE_LANDED_SUPPORT', SwingDamageLandedSupport::class],
            'range'                       => ['RANGE_DAMAGE', Range::class],
            'spell'                       => ['SPELL_DAMAGE', Spell::class],
            'spell cast success'          => ['SPELL_CAST_SUCCESS', Spell::class],
            'spell aura applied'          => ['SPELL_AURA_APPLIED', Spell::class],
            // #4071: these resolved to Spell for as long as SPELL was listed above them in the mapping
            'spell periodic damage'   => ['SPELL_PERIODIC_DAMAGE', SpellPeriodic::class],
            'spell periodic heal'     => ['SPELL_PERIODIC_HEAL', SpellPeriodic::class],
            'spell periodic energize' => ['SPELL_PERIODIC_ENERGIZE', SpellPeriodic::class],
            'spell periodic missed'   => ['SPELL_PERIODIC_MISSED', SpellPeriodic::class],
            'spell building damage'   => ['SPELL_BUILDING_DAMAGE', SpellBuilding::class],
            'spell building heal'     => ['SPELL_BUILDING_HEAL', SpellBuilding::class],
        ];
    }

    #[Test]
    public function createFromEventName_givenAnUnknownEventName_throwsException(): void
    {
        // Arrange
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to find prefix for ENCOUNTER_START!');

        // Act
        Prefix::createFromEventName(CombatLogVersion::RETAIL_11_0_5, 'ENCOUNTER_START');

        // Assert - handled by expectException
    }

    /**
     * The invariant behind #4071: createFromEventName() returns the first Str::startsWith hit, so an entry listed
     * below one of its own prefixes can never be reached. Asserting the ordering directly catches a future entry
     * that the per-event-name table above would not think to cover.
     */
    #[Test]
    public function createFromEventName_givenTheDeclaredMappingOrder_returnsEveryMappedPrefixClass(): void
    {
        // Arrange
        $mapping = (new ReflectionClass(Prefix::class))->getConstant('PREFIX_CLASS_MAPPING');
        /** @var array<string, class-string<Prefix>> $mapping */
        $prefixes = array_keys($mapping);

        // Act
        $unreachable = [];
        foreach ($prefixes as $index => $prefix) {
            foreach (array_slice($prefixes, 0, $index) as $earlierPrefix) {
                if (Str::startsWith($prefix, $earlierPrefix)) {
                    $unreachable[] = sprintf('%s is unreachable: %s is listed above it', $prefix, $earlierPrefix);
                }
            }
        }

        // Assert
        $this->assertSame([], $unreachable, implode(PHP_EOL, $unreachable));
    }

    /**
     * The reparenting that made the reorder safe (#4071). Every consumer gating on the Spell prefix - including the
     * four collectors that take a typed `Spell $prefix` parameter - keeps accepting periodic and building events
     * exactly as it did while those two classes were unreachable.
     */
    #[Test]
    #[DataProvider('createFromEventName_givenAPeriodicOrBuildingEvent_returnsAPrefixThatIsAlsoASpellAndARange_DataProvider')]
    public function createFromEventName_givenAPeriodicOrBuildingEvent_returnsAPrefixThatIsAlsoASpellAndARange(
        string $eventName,
    ): void {
        // Arrange
        // Act
        $prefix = Prefix::createFromEventName(CombatLogVersion::RETAIL_11_0_5, $eventName);

        // Assert
        $this->assertInstanceOf(Spell::class, $prefix);
        $this->assertInstanceOf(Range::class, $prefix);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function createFromEventName_givenAPeriodicOrBuildingEvent_returnsAPrefixThatIsAlsoASpellAndARange_DataProvider(): array
    {
        return [
            'spell periodic damage' => ['SPELL_PERIODIC_DAMAGE'],
            'spell periodic heal'   => ['SPELL_PERIODIC_HEAL'],
            'spell building damage' => ['SPELL_BUILDING_DAMAGE'],
        ];
    }
}
