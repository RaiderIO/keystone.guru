<?php

namespace App\Console\Commands\Dungeon;

use App\Models\Dungeon;
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
    protected $signature = 'dungeon:createmissing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Looks into the internal code database for dungeons that haven't been added to the database yet and adds them.";

    public function handle(): int
    {
        /** @var Collection<Expansion> $expansions */
        $expansions = Expansion::all()->keyBy('shortname');
        /** @var Collection<Dungeon> $dungeons */
        $dungeons = Dungeon::all()->keyBy('key');

        foreach (Dungeon::ALL as $expansionKey => $dungeonKeys) {
            // Temp, I just want classic dungeons for now
            if ($expansionKey !== Expansion::EXPANSION_MOP) {
                continue;
            }

            foreach ($dungeonKeys as $dungeonKey) {
                // Already exists, we no longer care
                if ($dungeons->has($dungeonKey)) {
                    continue;
                }

                /** @var Expansion $expansion */
                $expansion = $expansions->get($expansionKey);

                $nameTranslationKey = sprintf('dungeons.%s.%s.name', $expansionKey, $dungeonKey);
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
                    'key'               => $dungeonKey,
                    'slug'              => Str::slug($nameTranslated),
                ]);

                $this->info(sprintf('- Added new dungeon %s', $nameTranslated));
            }
        }

        return 0;
    }
}
