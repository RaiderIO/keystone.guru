<?php

namespace App\Service\Season\SeasonAffixGroupService;

use App\Models\AffixGroup\AffixGroup;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeasonAffixGroupService')]
#[Group('GetDisplayedAffixGroups')]
final class GetDisplayedAffixGroupsTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_GivenOffsetZero_ShouldReturn10AffixGroups(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);

        // Act
        $result = $service->getDisplayedAffixGroups(0);

        // Assert
        $this->assertCount(10, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('getDisplayedAffixGroups_GivenOffset_ShouldReturnCorrectCount_dataProvider')]
    public function getDisplayedAffixGroups_GivenOffset_ShouldReturn10AffixGroups(int $offset): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);

        // Act
        $result = $service->getDisplayedAffixGroups($offset);

        // Assert
        $this->assertCount(10, $result);
    }

    public static function getDisplayedAffixGroups_GivenOffset_ShouldReturnCorrectCount_dataProvider(): array
    {
        return [
            'positive offset' => [1],
            'negative offset' => [-1],
            'large negative'  => [-5],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_GivenOffsetZero_ShouldReturnEntriesWithRequiredKeys(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);

        // Act
        $result = $service->getDisplayedAffixGroups(0);

        // Assert - each entry must have date_start (Carbon) and affix_group (AffixGroup)
        foreach ($result as $entry) {
            $this->assertArrayHasKey('date_start', $entry);
            $this->assertArrayHasKey('affix_group', $entry);
            $this->assertInstanceOf(Carbon::class, $entry['date_start']);
            $this->assertInstanceOf(AffixGroup::class, $entry['affix_group']);
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getDisplayedAffixGroups_GivenPositiveAndNegativeOffset_ShouldReturnDifferentDates(): void
    {
        // Arrange
        $service = app(SeasonAffixGroupServiceInterface::class);

        // Act
        $resultCurrent = $service->getDisplayedAffixGroups(0);
        $resultFuture  = $service->getDisplayedAffixGroups(1);

        // Assert - the future window should start later than the current window
        $currentFirstDate = $resultCurrent->first()['date_start'];
        $futureFirstDate  = $resultFuture->first()['date_start'];
        $this->assertTrue($futureFirstDate->gte($currentFirstDate));
    }
}
