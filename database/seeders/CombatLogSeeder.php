<?php

namespace Database\Seeders;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\ParsedCombatLog;
use FilesystemIterator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Str;

class CombatLogSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootDir         = database_path('seeders/combatlogs/');
        $rootDirIterator = new FilesystemIterator($rootDir);

        $combatLogNpcEventAttributes   = [];
        $combatLogSpellEventAttributes = [];
        $parsedCombatLogAttributes     = [];

        // Iterate over all saved releases
        foreach ($rootDirIterator as $combatLogSeederDataFilePath) {
            // Only JSON files
            if (!Str::endsWith($combatLogSeederDataFilePath, '.json')) {
                continue;
            }

            $modelJson = file_get_contents($combatLogSeederDataFilePath);
            // Convert to models
            $modelsData = json_decode($modelJson, true);

            foreach ($modelsData as $modelData) {
                // If the models don't contain timestamps, don't try to set them
                if (!isset($modelData['created_at'])) {
                    continue;
                }

                // Using ParsedCombatLog, but it doesn't matter for other models - it comes from a trait
                $modelData['created_at'] = Carbon::createFromFormat(ParsedCombatLog::SERIALIZED_DATE_TIME_FORMAT, $modelData['created_at'])->toDateTimeString();
                $modelData['updated_at'] = Carbon::createFromFormat(ParsedCombatLog::SERIALIZED_DATE_TIME_FORMAT, $modelData['updated_at'])->toDateTimeString();
            }

            if (str_contains($combatLogSeederDataFilePath, 'combat_log_npc_events')) {
                $combatLogNpcEventAttributes = $modelsData;
            } elseif (str_contains($combatLogSeederDataFilePath, 'combat_log_spell_events')) {
                $combatLogSpellEventAttributes = $modelsData;
            } elseif (str_contains($combatLogSeederDataFilePath, 'parsed_combat_logs')) {
                $parsedCombatLogAttributes = $modelsData;
            } else {
                throw new \Exception(sprintf('Unknown .json file found in combatlogs directory: %s', $combatLogSeederDataFilePath));
            }
        }

        // Insert the data into the database
        collect($combatLogNpcEventAttributes)->chunk(1000)->each(function (Collection $chunk) {
            CombatLogNpcEvent::from(DatabaseSeeder::getTempTableName(CombatLogNpcEvent::class))
                ->insert($chunk->toArray());
        });
        collect($combatLogSpellEventAttributes)->chunk(1000)->each(function (Collection $chunk) {
            CombatLogSpellEvent::from(DatabaseSeeder::getTempTableName(CombatLogSpellEvent::class))
                ->insert($chunk->toArray());
        });
        collect($parsedCombatLogAttributes)->chunk(1000)->each(function (Collection $chunk) {
            ParsedCombatLog::from(DatabaseSeeder::getTempTableName(ParsedCombatLog::class))
                ->insert($chunk->toArray());
        });
    }

    public static function getAffectedModelClasses(): array
    {
        return [
            CombatLogNpcEvent::class,
            CombatLogSpellEvent::class,
            ParsedCombatLog::class,
        ];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // Skip staging since it will collide with testing - testing will always run,
        // staging only when a release is staged
        return [
            'local',
            'testing',
            'production',
        ];
    }
}
