<?php

namespace Tests\Feature\App\Service\Season\SeasonAffixGroupService;

use App\Models\Affix;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonAffixGroupService')]
#[Group('GetAffixGroupAt')]
final class GetAffixGroupAtTest extends PublicTestCase
{
    /**
     * @param list<string> $expectedAffixKeys
     *
     * @throws Exception
     */
    #[Test]
    #[DataProvider('getAffixGroupAt_givenMidnightS2Week_returnsTheAffixesRaiderIoAnnounced_dataProvider')]
    public function getAffixGroupAt_givenMidnightS2Week_returnsTheAffixesRaiderIoAnnounced(
        string $date,
        array  $expectedAffixKeys,
    ): void {
        // Arrange
        $service    = app(SeasonAffixGroupServiceInterface::class);
        $midnightS2 = Season::findOrFail(Season::SEASON_MIDNIGHT_S2);
        $usRegion   = GameServerRegion::where('short', GameServerRegion::AMERICAS)->firstOrFail();

        // Act
        $affixGroup = $service->getAffixGroupAt($midnightS2, Carbon::parse($date, 'UTC'), $usRegion);

        // Assert
        $this->assertNotNull($affixGroup);
        $this->assertSame($expectedAffixKeys, $affixGroup->affixes->pluck('key')->all());
    }

    /**
     * Raider.IO's weekly US affix announcements; each date is the Wednesday after that week's Tuesday reset.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function getAffixGroupAt_givenMidnightS2Week_returnsTheAffixesRaiderIoAnnounced_dataProvider(): array
    {
        $fortifiedFirst = static fn(string $bargain): array => [
            Affix::AFFIX_LINDORMIS_GUIDANCE,
            Affix::AFFIX_FORTIFIED,
            Affix::AFFIX_TYRANNICAL,
            $bargain,
            Affix::AFFIX_XALATATHS_GUILE,
        ];
        $tyrannicalFirst = static fn(string $bargain): array => [
            Affix::AFFIX_LINDORMIS_GUIDANCE,
            Affix::AFFIX_TYRANNICAL,
            Affix::AFFIX_FORTIFIED,
            $bargain,
            Affix::AFFIX_XALATATHS_GUILE,
        ];

        return [
            '2026-08-18' => ['2026-08-19 12:00:00', $fortifiedFirst(Affix::AFFIX_XALATATHS_BARGAIN_PULSAR)],
            '2026-08-25' => ['2026-08-26 12:00:00', $tyrannicalFirst(Affix::AFFIX_XALATATHS_BARGAIN_VOIDBOUND)],
            '2026-09-01' => ['2026-09-02 12:00:00', $fortifiedFirst(Affix::AFFIX_XALATATHS_BARGAIN_DEVOUR)],
            '2026-09-08' => ['2026-09-09 12:00:00', $tyrannicalFirst(Affix::AFFIX_XALATATHS_BARGAIN_PULSAR)],
            '2026-09-15' => ['2026-09-16 12:00:00', $fortifiedFirst(Affix::AFFIX_XALATATHS_BARGAIN_ASCENDANT)],
            '2026-09-22' => ['2026-09-23 12:00:00', $tyrannicalFirst(Affix::AFFIX_XALATATHS_BARGAIN_VOIDBOUND)],
            '2026-09-29' => ['2026-09-30 12:00:00', $fortifiedFirst(Affix::AFFIX_XALATATHS_BARGAIN_DEVOUR)],
            '2026-10-06' => ['2026-10-07 12:00:00', $tyrannicalFirst(Affix::AFFIX_XALATATHS_BARGAIN_ASCENDANT)],
        ];
    }
}
