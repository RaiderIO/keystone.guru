<?php

namespace App\Console\Commands\Dungeon;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Expansion;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreateMissing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeon:createmissing {expansion : The expansion key to add missing dungeons for, e.g. classic}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Looks into the internal code database for dungeons that haven't been added to the database yet and adds them.";

    public function handle(): int
    {
        /** @var Collection<string, Expansion> $expansions */
        $expansions = Expansion::all()->keyBy('key');
        /** @var Collection<string, Dungeon> $dungeons */
        $dungeons = Dungeon::all()->keyBy('key');

        $onlyExpansionKey = $this->argument('expansion');
        if (!isset(Expansion::ALL[$onlyExpansionKey])) {
            $this->error(sprintf('Unknown expansion %s', $onlyExpansionKey));

            return self::FAILURE;
        }

        foreach (DungeonKey::casesByExpansionKey() as $expansionKey => $dungeonKeys) {
            // Not every expansion's dungeons are wanted in the database (e.g. TBC, Cata), so only add them one expansion at a time
            if ($expansionKey !== $onlyExpansionKey) {
                continue;
            }

            foreach ($dungeonKeys as $dungeonKey) {
                // Already exists, we no longer care
                if ($dungeons->has($dungeonKey->value)) {
                    continue;
                }

                /** @var Expansion $expansion */
                $expansion = $expansions->get($expansionKey);

                $nameTranslationKey = sprintf('dungeons.%s.%s.name', $expansionKey, $dungeonKey->value);
                $nameTranslated     = __($nameTranslationKey, [], 'en_US');

                Dungeon::create([
                    'expansion_id'      => $expansion->id,
                    'active'            => 0,
                    'speedrun_enabled'  => false,
                    'zone_id'           => 123,
                    'map_id'            => 123,
                    'challenge_mode_id' => 123,
                    'mdt_id'            => 123,
                    'name'              => $nameTranslationKey,
                    'key'               => $dungeonKey->value,
                    'slug'              => Str::slug($nameTranslated),
                ]);

                $this->info(sprintf('- Added new dungeon %s', $nameTranslated));
            }
        }

        return 0;
    }
}
