<?php

namespace Tests\Fixtures\Seeders;

use Database\Seeders\TableSeederInterface;
use Illuminate\Database\Seeder;

class StubTableSeeder extends Seeder implements TableSeederInterface
{
    public static int $runCount = 0;

    public function run(): void
    {
        self::$runCount++;
    }

    public static function getAffectedModelClasses(): array
    {
        return [StubSeededModel::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        return null;
    }
}
