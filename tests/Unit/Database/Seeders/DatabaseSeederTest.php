<?php

namespace Tests\Unit\Database\Seeders;

use Database\Seeders\DatabaseSeeder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DatabaseSeederTest extends TestCase
{
    #[Test]
    public function allSucceeded_givenFirstItemFails_stillInvokesCallbackForEveryRemainingItem(): void
    {
        // Arrange - regression test for #3642: a failure for the first item must not short-circuit
        // the remaining items, and must not be masked once a later item succeeds.
        $method  = new ReflectionMethod(DatabaseSeeder::class, 'allSucceeded');
        $invoked = [];
        $results = [false, true, true];

        // Act
        $allSucceeded = $method->invoke(null, ['a', 'b', 'c'], function (string $item) use (&$invoked, $results): bool {
            $invoked[] = $item;

            return $results[array_search($item, ['a', 'b', 'c'], true)];
        });

        // Assert
        $this->assertSame(['a', 'b', 'c'], $invoked, 'Every item must be invoked regardless of an earlier failure');
        $this->assertFalse($allSucceeded, 'A single failed item must not be masked by later successes');
    }

    #[Test]
    public function allSucceeded_givenAllItemsSucceed_returnsTrue(): void
    {
        // Arrange
        $method = new ReflectionMethod(DatabaseSeeder::class, 'allSucceeded');

        // Act
        $allSucceeded = $method->invoke(null, ['a', 'b', 'c'], fn(string $item): bool => true);

        // Assert
        $this->assertTrue($allSucceeded);
    }

    #[Test]
    public function allSucceeded_givenLastItemFails_returnsFalse(): void
    {
        // Arrange - regression test for #3642: the original bug reset the failure flag back to
        // false whenever a later call succeeded, so a failure confined to the last item was
        // enough to hide it entirely.
        $method = new ReflectionMethod(DatabaseSeeder::class, 'allSucceeded');

        // Act
        $allSucceeded = $method->invoke(null, ['a', 'b', 'c'], fn(string $item): bool => $item !== 'c');

        // Assert
        $this->assertFalse($allSucceeded);
    }
}
