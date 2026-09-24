<?php

namespace Tests\Unit\App\Models;

use App\Models\PublishedState;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class PublishedStateTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('visibilityComparisonProvider')]
    public function isMoreVisibleThan_givenTwoStates_returnsWhetherTheFirstIsMoreVisible(
        string $publishedState,
        string $otherPublishedState,
        bool   $expected,
    ): void {
        // Arrange
        // Act
        $result = PublishedState::isMoreVisibleThan($publishedState, $otherPublishedState);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: string, 1: string, 2: bool}> */
    public static function visibilityComparisonProvider(): array
    {
        return [
            'team over unpublished'             => [PublishedState::TEAM, PublishedState::UNPUBLISHED, true],
            'world with link over team'         => [PublishedState::WORLD_WITH_LINK, PublishedState::TEAM, true],
            'world over world with link'        => [PublishedState::WORLD, PublishedState::WORLD_WITH_LINK, true],
            'world over unpublished'            => [PublishedState::WORLD, PublishedState::UNPUBLISHED, true],
            'unpublished under world'           => [PublishedState::UNPUBLISHED, PublishedState::WORLD, false],
            'team under world with link'        => [PublishedState::TEAM, PublishedState::WORLD_WITH_LINK, false],
            'the same state is not more'        => [PublishedState::WORLD, PublishedState::WORLD, false],
            'unpublished is not more than self' => [PublishedState::UNPUBLISHED, PublishedState::UNPUBLISHED, false],
        ];
    }

    /**
     * @param array<int, string> $expected
     */
    #[Test]
    #[DataProvider('lessVisibleThanProvider')]
    public function getLessVisibleThan_givenAState_returnsEveryLessVisibleStateLeastVisibleFirst(string $publishedState, array $expected): void
    {
        // Arrange
        // Act
        $result = PublishedState::getLessVisibleThan($publishedState);

        // Assert
        $this->assertSame($expected, $result->all());
    }

    /** @return array<string, array{0: string, 1: array<int, string>}> */
    public static function lessVisibleThanProvider(): array
    {
        return [
            'unpublished'     => [PublishedState::UNPUBLISHED, []],
            'team'            => [PublishedState::TEAM, [PublishedState::UNPUBLISHED]],
            'world with link' => [PublishedState::WORLD_WITH_LINK, [PublishedState::UNPUBLISHED, PublishedState::TEAM]],
            'world'           => [PublishedState::WORLD, [PublishedState::UNPUBLISHED, PublishedState::TEAM, PublishedState::WORLD_WITH_LINK]],
        ];
    }

    #[Test]
    public function visibilityOrder_givenEveryKnownState_holdsEachExactlyOnce(): void
    {
        // Arrange
        $knownPublishedStates = array_keys(PublishedState::ALL);

        // Act
        $orderedPublishedStates = PublishedState::VISIBILITY_ORDER;

        // Assert
        $this->assertEqualsCanonicalizing($knownPublishedStates, $orderedPublishedStates);
        $this->assertSame(count(array_unique($orderedPublishedStates)), count($orderedPublishedStates));
    }

    #[Test]
    public function isMoreVisibleThan_givenAnUnknownState_throwsInvalidArgumentException(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        PublishedState::isMoreVisibleThan('not_a_published_state', PublishedState::WORLD);

        // Assert (by the expected exception)
    }
}
