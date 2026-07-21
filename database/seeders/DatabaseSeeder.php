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

    /** @var array<int, class-string<TableSeederInterface>> */
    private const SEEDERS = [
        // Seeders that don't depend on anything else
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
        MapIconTypesSeeder::class,
        TagCategorySeeder::class,
        PublishedStatesSeeder::class,
        CharacteristicsSeeder::class,
        TranslationsSeeder::class,
        MDTAddonVersionSeeder::class,

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
    /**
     * @param array<int, class-string<TableSeederInterface>>|null $seederClasses
     */
    public function run(CacheServiceInterface $cacheService, ?array $seederClasses = null): void
    {
        foreach ($seederClasses ?? [] as $seederClass) {
            if (!class_exists($seederClass)) {
                throw new Exception('Seeder class ' . $seederClass . ' does not exist!');
            }
        }

        self::$running = true;

        // $this->call(UsersTableSeeder::class);
        // $this->call(LaratrustSeeder::class);

        // 1. Prepare: Create a temporary table for all affected classes
        // 2. Apply: Seed the data into it
        // 2.1 During applying, seeder can do what it wants, it's all wrapped in a transaction
        // 3. Cleanup: Remove existing table, rename temporary table

        foreach ($seederClasses ?? self::SEEDERS as $seederClass) {
            /** @var class-string<TableSeederInterface> $seederClass */
            $affectedEnvironments = $seederClass::getAffectedEnvironments();
            if ($affectedEnvironments !== null && !in_array(app()->environment(), $affectedEnvironments)) {
                $this->command->info(
                    sprintf(
                        'Skipping %s because it is not meant for this environment (%s vs %s).',
                        $seederClass,
                        app()->environment(),
                        implode(', ', $affectedEnvironments),
                    ),
                );

                continue;
            }

            $affectedModelClasses = [];

            try {
                $affectedModelClasses = $seederClass::getAffectedModelClasses();

                $prepareFailed = self::anyFailed(
                    $affectedModelClasses,
                    fn(string $affectedModel): bool => $this->prepareTempTableForModel($affectedModel),
                );

                if ($prepareFailed) {
                    $this->command->error(sprintf('Preparing temp table for %s failed!', $seederClass));

                    break;
                }

                DB::transaction(function () use ($seederClass) {
                    $this->call($seederClass);
                });

                $applyFailed = self::anyFailed(
                    $affectedModelClasses,
                    fn(string $affectedModelClass): bool => $this->applyTempTableForModel($affectedModelClass),
                );

                if ($applyFailed) {
                    $this->command->error(sprintf('Applying temp table for %s failed!', $seederClass));

                    break;
                }
            } catch (Exception $e) {
                $this->command->error($e->getMessage());

                throw $e;
            } finally {
                $cleanupFailed = self::anyFailed(
                    $affectedModelClasses,
                    fn(string $affectedModelClass): bool => $this->cleanupTempTableForModel($affectedModelClass),
                );

                if ($cleanupFailed) {
                    $this->command->error(sprintf('Cleaning up temp table for %s failed!', $seederClass));
                }
            }
        }

        $cacheService->dropCaches();

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
            sprintf('CREATE TABLE %s LIKE %s;', $tableNameNew, $tableNameOld),
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
            ),
        );
    }

    private function cleanupTempTableForModel(string $className): bool
    {
        /** @var Model $instance */
        $instance = new $className();

        $tableNameNew = sprintf('%s%s', $instance->getTable(), self::TEMP_TABLE_SUFFIX);

        // IF EXISTS: a model whose prepare/apply step already failed never had its temp table
        // created, so cleanup must tolerate that instead of throwing and masking the real error
        // that's still propagating out of the finally block (see #3642).
        return DB::connection($instance->getConnectionName())->statement(
            sprintf('DROP TABLE IF EXISTS %s;', $tableNameNew),
        );
    }

    /**
     * Invokes $callback for every item and reports whether any call failed. Every item is always
     * attempted, regardless of an earlier item returning false (see #3642). This guarantee only
     * holds for callbacks that signal failure by returning false - a callback that throws still
     * aborts the remaining items, since the exception propagates out of array_map().
     *
     * @param array<int, class-string>     $items
     * @param callable(class-string): bool $callback
     */
    private static function anyFailed(array $items, callable $callback): bool
    {
        return in_array(false, array_map($callback, $items), true);
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
