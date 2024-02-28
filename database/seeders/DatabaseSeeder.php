<?php

namespace Database\Seeders;

use App\Models\Traits\SeederModel;
use App\Service\Cache\CacheServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public static bool $running = false;

    public const TEMP_TABLE_SUFFIX = '_temp';

    private const SEEDERS = [
        // Seeders which don't depend on anything else
        ExpansionsSeeder::class,
        GameServerRegionsSeeder::class,
        GameVersionsSeeder::class,
        RouteAttributesSeeder::class,
        PatreonBenefitsSeeder::class,
        FactionsSeeder::class,
        NpcClassificationsSeeder::class,
        NpcClassesSeeder::class,
        NpcTypesSeeder::class,
        RaidMarkersSeeder::class,
        ReleaseChangelogCategorySeeder::class,
        ReleasesSeeder::class,
        MapIconTypesSeeder::class,
        TagCategorySeeder::class,
        PublishedStatesSeeder::class,

        // Depends on ExpansionsSeeder, SeasonsSeeder
        AffixSeeder::class,

        // Depends on SeasonsSeeder, AffixSeeder
        TimewalkingEventSeeder::class,

        //  Depends on Factions
        CharacterInfoSeeder::class,

        // Depends on Expansions
        DungeonDataSeeder::class,

        // Depends on DungeonDataSeeder
        MappingVersionSeeder::class,

        // Depends on ExpansionsSeeder, DungeonDataSeeder
        SeasonsSeeder::class,
    ];

    /**
     * Run the database seeds.
     *
     *
     * @throws Throwable
     */
    public function run(CacheServiceInterface $cacheService): void
    {
        self::$running = true;

        $cacheService->dropCaches();
        // $this->call(UsersTableSeeder::class);
        // $this->call(LaratrustSeeder::class);

        // 1. Prepare: Create a temporary table for all affected classes
        // 2. Apply: Seed the data into it
        // 2.1 During applying, seeder can do what it wants, it's all wrapped in a transaction
        // 3. Cleanup: Remove existing table, rename temporary table

        foreach (self::SEEDERS as $seederClass) {
            try {
                $prepareFailed = false;

                /** @var TableSeederInterface $seederClass */
                $affectedModelClasses = $seederClass::getAffectedModelClasses();
                foreach ($affectedModelClasses as $affectedModel) {
                    $prepareFailed = !$prepareFailed && !$this->prepareTempTableForModel($affectedModel);
                }

                if ($prepareFailed) {
                    $this->command->error(sprintf('Preparing temp table for %s failed!', $seederClass));

                    break;
                }

                DB::transaction(function () use ($seederClass) {
                    $this->call($seederClass);
                });

                $applyFailed = false;
                foreach ($affectedModelClasses as $affectedModelClass) {
                    $applyFailed = !$applyFailed && !$this->applyTempTableForModel($affectedModelClass);
                }

                if ($applyFailed) {
                    $this->command->error(sprintf('Applying temp table for %s failed!', $seederClass));

                    break;
                }
            } finally {
                $cleanupFailed = false;
                foreach ($affectedModelClasses as $affectedModelClass) {
                    $cleanupFailed = !$cleanupFailed && !$this->cleanupTempTableForModel($affectedModelClass);
                }

                if ($cleanupFailed) {
                    $this->command->error(sprintf('Cleaning up temp table for %s failed!', $seederClass));
                }
            }
        }

        self::$running = false;
    }

    private function prepareTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameOld = $instance->getTable();
        $tableNameNew = sprintf('%s%s', $tableNameOld, self::TEMP_TABLE_SUFFIX);

        DB::table('files')->where('model_class', $className)->delete();

        return DB::statement(sprintf('CREATE TABLE %s LIKE %s;', $tableNameNew, $tableNameOld));
    }

    /**
     * @throws Throwable
     */
    private function applyTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameOld = $instance->getTable();
        $tableNameNew = sprintf('%s%s', $tableNameOld, self::TEMP_TABLE_SUFFIX);

        // Remove contents from old table, replace it with contents from new table
        //        DB::transaction(function () use ($tableNameOld, $tableNameNew, $className) {
        DB::table($tableNameOld)->truncate();

        return DB::statement(sprintf('INSERT INTO %s SELECT * FROM %s;', $tableNameOld, $tableNameNew));
        //        });
    }

    private function cleanupTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameNew = sprintf('%s%s', $instance->getTable(), self::TEMP_TABLE_SUFFIX);

        return DB::statement(sprintf('DROP TABLE %s;', $tableNameNew));
    }

    public static function getTempTableName(string $className): string
    {
        $result = Str::snake(Str::pluralStudly(class_basename($className)));

        // Only if we implement SeederModel - otherwise ignore it!
        if (in_array(SeederModel::class, class_uses_recursive($className))) {
            // See Model.php:getTable()
            $result = sprintf('%s%s', $result, self::TEMP_TABLE_SUFFIX);
        }

        return $result;
    }
}
