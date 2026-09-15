<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Prefixes\Swing;
use App\Logic\CombatLog\CombatEvents\Suffixes\Absorbed;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;
use App\Logic\CombatLog\CombatEvents\Suffixes\CastSuccess;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Heal;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogVersion;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the memoisation in Prefix/Suffix::createFromEventName(). Both remember the concrete class
 * per event name, so the properties worth pinning are that callers still get their own instance and
 * that a resolution under one combat log version never answers for another.
 */
#[Group('CombatLog')]
#[Group('AffixResolution')]
final class AffixResolutionTest extends PublicTestCase
{
    #[Test]
    public function createFromEventName_givenRepeatedCalls_returnsADistinctInstanceEachTime(): void
    {
        // Arrange
        $version   = CombatLogVersion::RETAIL_12_0_5;
        $eventName = 'SPELL_DAMAGE';

        // Act
        $firstSuffix  = Suffix::createFromEventName($version, $eventName);
        $secondSuffix = Suffix::createFromEventName($version, $eventName);
        $firstPrefix  = Prefix::createFromEventName($version, $eventName);
        $secondPrefix = Prefix::createFromEventName($version, $eventName);

        // Assert - setParameters() mutates the instance, so a shared one would leak between lines
        $this->assertNotSame($firstSuffix, $secondSuffix);
        $this->assertNotSame($firstPrefix, $secondPrefix);
        $this->assertSame($firstSuffix::class, $secondSuffix::class);
        $this->assertSame($firstPrefix::class, $secondPrefix::class);
    }

    #[Test]
    public function createFromEventName_givenInterleavedCombatLogVersions_resolvesPerVersion(): void
    {
        // Arrange
        $eventName = 'SPELL_DAMAGE';

        // Act - interleaved and repeated, so a version-blind cache cannot pass by luck
        $resolved = [];
        for ($round = 0; $round < 3; $round++) {
            foreach ([CombatLogVersion::RETAIL_11_0_2, CombatLogVersion::RETAIL_12_0_7] as $version) {
                $resolved[$version][] = Suffix::createFromEventName($version, $eventName)::class;
            }
        }

        // Assert
        $this->assertSame(array_fill(0, 3, DamageV20::class), $resolved[CombatLogVersion::RETAIL_11_0_2]);
        $this->assertSame(array_fill(0, 3, DamageV22::class), $resolved[CombatLogVersion::RETAIL_12_0_7]);
    }

    /**
     * @param class-string $expected
     */
    #[Test]
    #[DataProvider('createFromEventName_givenEventName_resolvesToClass_DataProvider')]
    public function createFromEventName_givenEventName_resolvesToClass(string $eventName, string $expected): void
    {
        // Arrange
        $version = CombatLogVersion::RETAIL_12_0_5;

        // Act - twice, so the answer is asserted on both the resolving and the memoised path
        $firstPrefix  = Prefix::createFromEventName($version, $eventName);
        $secondPrefix = Prefix::createFromEventName($version, $eventName);

        // Assert
        $this->assertInstanceOf($expected, $firstPrefix);
        $this->assertInstanceOf($expected, $secondPrefix);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function createFromEventName_givenEventName_resolvesToClass_DataProvider(): array
    {
        return [
            'Spell prefix' => [
                'eventName' => 'SPELL_DAMAGE',
                'expected'  => Spell::class,
            ],
            'Swing prefix' => [
                'eventName' => 'SWING_DAMAGE',
                'expected'  => Swing::class,
            ],
        ];
    }

    /**
     * The 8 builder-backed mapping entries take the is_subclass_of() true branch; the other 27 take
     * the plain `new $className()` branch, which is the half the instanceof rewrite touched.
     *
     * @param class-string $expected
     */
    #[Test]
    #[DataProvider('createFromEventName_givenANonBuilderSuffix_resolvesToClass_DataProvider')]
    public function createFromEventName_givenANonBuilderSuffix_resolvesToClass(string $eventName, string $expected): void
    {
        // Arrange
        $version = CombatLogVersion::RETAIL_12_0_5;

        // Act - twice, so the answer is asserted on both the resolving and the memoised path
        $firstSuffix  = Suffix::createFromEventName($version, $eventName);
        $secondSuffix = Suffix::createFromEventName($version, $eventName);

        // Assert
        $this->assertInstanceOf($expected, $firstSuffix);
        $this->assertInstanceOf($expected, $secondSuffix);
        $this->assertNotSame($firstSuffix, $secondSuffix);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function createFromEventName_givenANonBuilderSuffix_resolvesToClass_DataProvider(): array
    {
        return [
            'Heal' => [
                'eventName' => 'SPELL_HEAL',
                'expected'  => Heal::class,
            ],
            'Absorbed' => [
                'eventName' => 'SPELL_ABSORBED',
                'expected'  => Absorbed::class,
            ],
            'Cast success' => [
                'eventName' => 'SPELL_CAST_SUCCESS',
                'expected'  => CastSuccess::class,
            ],
            'Longest match wins over a shorter one it ends with' => [
                'eventName' => 'SPELL_AURA_BROKEN_SPELL',
                'expected'  => AuraBrokenSpell::class,
            ],
        ];
    }

    #[Test]
    public function createFromEventName_givenUnresolvableEventName_throwsEveryTime(): void
    {
        // Arrange
        $version   = CombatLogVersion::RETAIL_12_0_5;
        $eventName = 'DEFINITELY_NOT_A_REAL_EVENT';

        // Act & Assert - an unresolvable name must not be cached into something that stops throwing
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                Prefix::createFromEventName($version, $eventName);
                $this->fail('Expected an exception for an unresolvable event name');
            } catch (Exception $exception) {
                $this->assertStringContainsString('Unable to find prefix', $exception->getMessage());
            }

            try {
                Suffix::createFromEventName($version, $eventName);
                $this->fail('Expected an exception for an unresolvable event name');
            } catch (Exception $exception) {
                $this->assertStringContainsString('Unable to find suffix', $exception->getMessage());
            }
        }
    }
}
