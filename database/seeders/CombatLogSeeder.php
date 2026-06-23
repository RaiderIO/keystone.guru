<?php

namespace Database\Seeders;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\ParsedCombatLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CombatLogSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootDir = database_path('seeders/combatlogs/');

        foreach (self::getAffectedModelClasses() as $modelClass) {
            $fileName   = Str::snake(class_basename($modelClass)) . 's';
            $modelsData = json_decode(file_get_contents(sprintf('%s%s.json', $rootDir, $fileName)), true);
            /** @var array<int, array<string, mixed>> $modelsData */

            foreach ($modelsData as &$modelData) {
                if (!isset($modelData['created_at'])) {
                    continue;
                }

                // Using ParsedCombatLog, but it doesn't matter for other models - it comes from a trait
                $modelData['created_at'] = Carbon::createFromFormat(ParsedCombatLog::SERIALIZED_DATE_TIME_FORMAT, $modelData['created_at'])->toDateTimeString();
                if (isset($modelData['updated_at'])) {
                    $modelData['updated_at'] = Carbon::createFromFormat(ParsedCombatLog::SERIALIZED_DATE_TIME_FORMAT, $modelData['updated_at'])->toDateTimeString();
                }
            }
            unset($modelData);

            /** @var Collection<int, array<string, mixed>> $collected */
            $collected = collect($modelsData);
            $collected->chunk(1000)->each(function (Collection $chunk) use ($modelClass) {
                $modelClass::from(DatabaseSeeder::getTempTableName($modelClass))
                    ->insert($chunk->toArray());
            });
        }
    }

    /**
     * @return class-string[]
     */
    public static function getAffectedModelClasses(): array
    {
        return [
            CombatLogNpcEvent::class,
            CombatLogSpellEvent::class,
            ParsedCombatLog::class,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // Skip staging since it will collide with testing - testing will always run,
        // staging only when a release is staged
        return [
            'local',
            //            'testing',
            //            'production',
        ];
    }
}
