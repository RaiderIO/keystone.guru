<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\Heal;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellPeriodic;
use App\Logic\CombatLog\CombatEvents\Suffixes\Heal;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('Heal')]
final class HealTest extends PublicTestCase
{
    /**
     * Real 10.1.0 (COMBAT_LOG_VERSION 20) and 12.0.1 (COMBAT_LOG_VERSION 22) heal lines; their advanced data
     * blocks differ in length, so each is parsed under the versions sharing its advanced data layout.
     */
    private const string LINE_V20_PREFIX = '5/10 16:05:17.318  SPELL_PERIODIC_HEAL,Player-3684-0C47A8E2,"Laililina-Mal\'Ganis",0x512,0x1,Player-86-09D1DAEB,"Malachi-Terenas",0x512,0x2,774,"Rejuvenation",0x8,Player-86-09D1DAEB,0000000000000000,693560,693560,11777,2172,14050,0,0,50000,50000,0,636.72,1253.03,1041,6.2321,423,';

    private const string LINE_V22_PREFIX = '3/25/2026 10:38:29.4491  SPELL_HEAL,Player-1303-09231FEC,"Riptidewave-Aggra(Português)-EU",0x512,0x80000000,Player-580-0AE12FF4,"Palatsch-Blackmoore-EU",0x512,0x80000020,61295,"Riptide",0x8,Player-580-0AE12FF4,0000000000000000,291718,446020,2494,698,4052,414,0,0,0,250000,250000,0,1801.07,-3077.86,2097,5.2480,247,';

    private const array VERSIONS_V20 = [
        CombatLogVersion::RETAIL_10_1_0,
        CombatLogVersion::RETAIL_11_0_2,
    ];

    #[Test]
    #[DataProvider('parseEvent_givenHealEvent_returnsEveryField_dataProvider')]
    public function parseEvent_givenHealEvent_returnsEveryField(
        int    $combatLogVersion,
        string $healEvent,
        string $expectedPrefixClass,
        int    $expectedAmount,
        int    $expectedBaseAmount,
        int    $expectedOverHealing,
        int    $expectedAbsorbed,
        bool   $expectedIsCritical,
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($healEvent);

        // Act
        /** @var AdvancedCombatLogEvent $result */
        $result = $combatLogEntry->parseEvent([], $combatLogVersion);

        // Assert
        Assert::assertInstanceOf(AdvancedCombatLogEvent::class, $result);
        Assert::assertInstanceOf($expectedPrefixClass, $result->getPrefix());
        /** @var Heal $suffix */
        $suffix = $result->getSuffix();
        Assert::assertInstanceOf(Heal::class, $suffix);
        Assert::assertSame($expectedAmount, $suffix->getAmount(), 'amount');
        Assert::assertSame($expectedBaseAmount, $suffix->getBaseAmount(), 'baseAmount');
        Assert::assertSame($expectedOverHealing, $suffix->getOverHealing(), 'overhealing');
        Assert::assertSame($expectedAbsorbed, $suffix->getAbsorbed(), 'absorbed');
        Assert::assertSame($expectedIsCritical, $suffix->isCritical(), 'critical');
    }

    /**
     * @return array<string, array{int, string, class-string, int, int, int, int, bool}>
     */
    public static function parseEvent_givenHealEvent_returnsEveryField_dataProvider(): array
    {
        $versionsV22 = array_values(array_filter(
            array_keys(CombatLogVersion::RETAIL_ALL),
            static fn(int $version): bool => !in_array($version, self::VERSIONS_V20, true),
        ));

        $cases = [];
        foreach (self::VERSIONS_V20 as $version) {
            // Every suffix field differs, so a field read from the wrong position cannot pass
            $cases[sprintf('%d: distinct fields, critical', $version)] = [
                $version, self::LINE_V20_PREFIX . '5729,6100,1200,371,1', SpellPeriodic::class, 5729, 6100, 1200, 371, true,
            ];
            // Unmodified real line: Rejuvenation on a target at full health, so all of it is overhealing
            $cases[sprintf('%d: real full overheal', $version)] = [
                $version, self::LINE_V20_PREFIX . '5729,5729,5729,0,nil', SpellPeriodic::class, 5729, 5729, 5729, 0, false,
            ];
        }

        foreach ($versionsV22 as $version) {
            $cases[sprintf('%d: distinct fields, critical', $version)] = [
                $version, self::LINE_V22_PREFIX . '19428,21370,4108,512,1', Spell::class, 19428, 21370, 4108, 512, true,
            ];
            // Unmodified real line: the target ends below full health, so there is no overhealing
            $cases[sprintf('%d: real no overheal', $version)] = [
                $version, self::LINE_V22_PREFIX . '19428,19428,0,0,nil', Spell::class, 19428, 19428, 0, 0, false,
            ];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('setParameters_givenCombatLogVersion_mapsEveryField_dataProvider')]
    public function setParameters_givenCombatLogVersion_mapsEveryField(int $combatLogVersion): void
    {
        // Arrange
        $suffix = Suffix::createFromEventName($combatLogVersion, 'SPELL_HEAL');

        // Act
        $suffix->setParameters([19428, 21370, 4108, 512, 1]);

        // Assert
        Assert::assertInstanceOf(Heal::class, $suffix);
        Assert::assertSame(19428, $suffix->getAmount(), 'amount');
        Assert::assertSame(21370, $suffix->getBaseAmount(), 'baseAmount');
        Assert::assertSame(4108, $suffix->getOverHealing(), 'overhealing');
        Assert::assertSame(512, $suffix->getAbsorbed(), 'absorbed');
        Assert::assertTrue($suffix->isCritical(), 'critical');
    }

    /**
     * @return array<int, array{int}>
     */
    public static function setParameters_givenCombatLogVersion_mapsEveryField_dataProvider(): array
    {
        return array_map(static fn(int $version): array => [$version], array_keys(CombatLogVersion::ALL));
    }

    #[Test]
    public function setParameters_givenNilCriticalFlag_returnsNotCritical(): void
    {
        // Arrange
        $suffix = Suffix::createFromEventName(CombatLogVersion::RETAIL_12_0_1, 'SPELL_HEAL');

        // Act
        $suffix->setParameters([19428, 21370, 4108, 512, 'nil']);

        // Assert
        Assert::assertInstanceOf(Heal::class, $suffix);
        Assert::assertFalse($suffix->isCritical());
    }
}
