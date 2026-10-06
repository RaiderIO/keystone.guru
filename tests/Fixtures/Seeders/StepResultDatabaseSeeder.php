<?php

namespace Tests\Fixtures\Seeders;

use App\Service\Cache\CacheServiceInterface;
use Database\Seeders\DatabaseSeeder;

/**
 * Runs only StubTableSeeder and reports the given result for its temp-table prepare and apply steps, so
 * a test can fail either step without touching the seeded test database.
 */
class StepResultDatabaseSeeder extends DatabaseSeeder
{
    public function __construct(
        private readonly bool $prepareSucceeds,
        private readonly bool $applySucceeds,
    ) {
    }

    /**
     * @param array<int, class-string<\Database\Seeders\TableSeederInterface>>|null $seederClasses
     */
    public function run(CacheServiceInterface $cacheService, ?array $seederClasses = null): void
    {
        parent::run($cacheService, $seederClasses ?? [StubTableSeeder::class]);
    }

    protected function prepareTempTableForModel(string $className): bool
    {
        return $this->prepareSucceeds;
    }

    protected function applyTempTableForModel(string $className): bool
    {
        return $this->applySucceeds;
    }
}
