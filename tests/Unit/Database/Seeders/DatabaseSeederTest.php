<?php

namespace Tests\Unit\Database\Seeders;

use App\Exceptions\SeederStepFailedException;
use App\Models\RaidMarker;
use App\Service\Cache\CacheServiceInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Fixtures\Seeders\StepResultDatabaseSeeder;
use Tests\Fixtures\Seeders\StubTableSeeder;
use Tests\TestCase;
use Throwable;

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
    public function cleanupTempTableForModel_givenAnExistingTempTable_dropsIt(): void
    {
        // Arrange
        $tempTable = DatabaseSeeder::getTempTableName(RaidMarker::class);
        DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        DB::statement(sprintf('CREATE TABLE %s LIKE %s;', $tempTable, (new RaidMarker())->getTable()));

        $method = new ReflectionMethod(DatabaseSeeder::class, 'cleanupTempTableForModel');

        try {
            // Act
            $result = $method->invoke(new DatabaseSeeder(), RaidMarker::class);

            // Assert
            $this->assertTrue($result);
            $this->assertFalse(Schema::hasTable($tempTable));
        } finally {
            DB::statement(sprintf('DROP TABLE IF EXISTS %s;', $tempTable));
        }
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

    #[Test]
    public function run_givenPrepareStepFails_throwsSeederStepFailedException(): void
    {
        // Arrange
        $this->bindStepResultSeeder(prepareSucceeds: false, applySucceeds: true, expectedDropCachesCalls: 0);

        // Act
        $exception = $this->runDbSeed();

        // Assert
        $this->assertInstanceOf(SeederStepFailedException::class, $exception);
        $this->assertSame(sprintf('Preparing temp table for %s failed!', StubTableSeeder::class), $exception->getMessage());
        $this->assertSame(0, StubTableSeeder::$runCount, 'The seeder must not run once its temp table failed to prepare');
    }

    #[Test]
    public function run_givenApplyStepFails_throwsSeederStepFailedException(): void
    {
        // Arrange
        $this->bindStepResultSeeder(prepareSucceeds: true, applySucceeds: false, expectedDropCachesCalls: 0);

        // Act
        $exception = $this->runDbSeed();

        // Assert
        $this->assertInstanceOf(SeederStepFailedException::class, $exception);
        $this->assertSame(sprintf('Applying temp table for %s failed!', StubTableSeeder::class), $exception->getMessage());
        $this->assertSame(1, StubTableSeeder::$runCount);
    }

    #[Test]
    public function run_givenEveryStepSucceeds_seedsAndDropsCaches(): void
    {
        // Arrange
        $this->bindStepResultSeeder(prepareSucceeds: true, applySucceeds: true, expectedDropCachesCalls: 1);

        // Act
        $exception = $this->runDbSeed();

        // Assert
        $this->assertNull($exception);
        $this->assertSame(1, StubTableSeeder::$runCount);
    }

    private function bindStepResultSeeder(bool $prepareSucceeds, bool $applySucceeds, int $expectedDropCachesCalls): void
    {
        StubTableSeeder::$runCount = 0;

        $cacheService = $this->createMock(CacheServiceInterface::class);
        $cacheService->expects($this->exactly($expectedDropCachesCalls))->method('dropCaches');

        $this->app->instance(CacheServiceInterface::class, $cacheService);
        $this->app->instance(DatabaseSeeder::class, new StepResultDatabaseSeeder($prepareSucceeds, $applySucceeds));
    }

    private function runDbSeed(): ?Throwable
    {
        try {
            $this->artisan('db:seed', ['--force' => true])->run();
        } catch (Throwable $exception) {
            return $exception;
        } finally {
            DatabaseSeeder::$running = false;
        }

        return null;
    }
}
