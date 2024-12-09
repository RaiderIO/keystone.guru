<?php

namespace Database\Seeders;

use App\Models\Traits\SeederModel;
use App\Service\Cache\CacheServiceInterface;
use Exception;
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
        // Combatlog
        CombatLogSeeder::class,

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
        CharacteristicsSeeder::class,
        TranslationsSeeder::class,

        // Depends on ExpansionsSeeder, SeasonsSeeder
        AffixSeeder::class,

        // Depends on SeasonsSeeder, AffixSeeder
        TimewalkingEventSeeder::class,

        //  Depends on Factions
        CharacterRacesSeeder::class,
        CharacterClassesSeeder::class,
        CharacterRaceClassesSeeder::class,
        CharacterClassSpecializationsSeeder::class,

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
    public function run(CacheServiceInterface $cacheService, array $seederClasses = null): void
    {
        foreach ($seederClasses ?? [] as $seederClass) {
            if (!class_exists($seederClass)) {
                throw new Exception('Seeder class ' . $seederClass . ' does not exist!');
            }
        }

        self::$running = true;

        $cacheService->dropCaches();
        // $this->call(UsersTableSeeder::class);
        // $this->call(LaratrustSeeder::class);

        // 1. Prepare: Create a temporary table for all affected classes
        // 2. Apply: Seed the data into it
        // 2.1 During applying, seeder can do what it wants, it's all wrapped in a transaction
        // 3. Cleanup: Remove existing table, rename temporary table

        foreach ($seederClasses ?? self::SEEDERS as $seederClass) {
            /** @var TableSeederInterface $seederClass */
            $affectedEnvironments = $seederClass::getAffectedEnvironments();
            if ($affectedEnvironments !== null && !in_array(app()->environment(), $affectedEnvironments)) {
                $this->command->info(
                    sprintf(
                        'Skipping %s because it is not meant for this environment (%s vs %s).',
                        $seederClass,
                        app()->environment(),
                        implode(', ', $affectedEnvironments)
                    )
                );

                continue;
            }

            $affectedModelClasses = [];

            try {
                $prepareFailed = false;

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
            } catch (Exception $e) {
                $this->command->error($e->getMessage());

                throw $e;
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

        return DB::connection($instance->getConnectionName())->statement(
            sprintf('CREATE TABLE %s LIKE %s;', $tableNameNew, $tableNameOld)
        );
    }

    /**
     * @throws Throwable
     */
    private function applyTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameOriginal = $instance->getTable();
        $tableNameNewData  = sprintf('%s%s', $tableNameOriginal, self::TEMP_TABLE_SUFFIX);

        // Rename tables in one statement to prevent any downtime
        return DB::connection($instance->getConnectionName())->statement(
            sprintf(
                'RENAME TABLE %s TO temp_table, %s TO %s, temp_table TO %s;',
                $tableNameOriginal,
                $tableNameNewData,
                $tableNameOriginal,
                $tableNameNewData,
            )
        );
    }

    private function cleanupTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameNew = sprintf('%s%s', $instance->getTable(), self::TEMP_TABLE_SUFFIX);

        return DB::connection($instance->getConnectionName())->statement(
            sprintf('DROP TABLE %s;', $tableNameNew)
        );
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
