<?php

namespace Tests\Unit\Database\Seeders;

use App\Models\RaidMarker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

#[Group('DatabaseSeeder')]
final class DatabaseSeederTest extends TestCase
{
    #[Test]
    public function cleanupTempTableForModel_givenTempTableWasNeverCreated_returnsTrueWithoutThrowing(): void
    {
        // Arrange - regression test for #3642: if an earlier model's prepare/apply step throws,
        // the finally block still calls cleanup for every model, including ones whose temp table
        // was never created. Cleanup must tolerate that instead of throwing and masking the
        // real exception already propagating out of the finally block.
        $tempTable = DatabaseSeeder::getTempTableName(RaidMarker::class);
        DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));

        $method = new ReflectionMethod(DatabaseSeeder::class, 'cleanupTempTableForModel');

        // Act
        $result = $method->invoke(new DatabaseSeeder(), RaidMarker::class);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function anyFailed_givenFirstItemFails_returnsTrueAfterInvokingEveryItem(): void
    {
        // Arrange - regression test for #3642: a failure for the first item must not short-circuit
        // the remaining items, and must not be masked once a later item succeeds.
        $method  = new ReflectionMethod(DatabaseSeeder::class, 'anyFailed');
        $invoked = [];
        $results = ['a' => false, 'b' => true, 'c' => true];

        // Act
        $anyFailed = $method->invoke(null, ['a', 'b', 'c'], function (string $item) use (&$invoked, $results): bool {
            $invoked[] = $item;

            return $results[$item];
        });

        // Assert
        $this->assertSame(['a', 'b', 'c'], $invoked, 'Every item must be invoked regardless of an earlier failure');
        $this->assertTrue($anyFailed, 'A single failed item must not be masked by later successes');
    }

    #[Test]
    public function anyFailed_givenAllItemsSucceed_returnsFalse(): void
    {
        // Arrange
        $method = new ReflectionMethod(DatabaseSeeder::class, 'anyFailed');

        // Act
        $anyFailed = $method->invoke(null, ['a', 'b', 'c'], fn(string $item): bool => true);

        // Assert
        $this->assertFalse($anyFailed);
    }

    #[Test]
    public function anyFailed_givenLastItemFails_returnsTrue(): void
    {
        // Arrange - regression test for #3642: the original bug reset the failure flag back to
        // false whenever a later call succeeded, so a failure confined to the last item was
        // enough to hide it entirely.
        $method = new ReflectionMethod(DatabaseSeeder::class, 'anyFailed');

        // Act
        $anyFailed = $method->invoke(null, ['a', 'b', 'c'], fn(string $item): bool => $item !== 'c');

        // Assert
        $this->assertTrue($anyFailed);
    }
}
