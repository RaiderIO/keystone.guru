<?php

namespace App\Console\Commands\Dungeon;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CreateMissingFloors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dungeon:createmissingfloors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the database for dungeons without floors, and if found, looks into the translation files for floors to add and adds them.';

    public function handle(): int
    {
        /** @var Collection<Dungeon> $dungeons */
        $dungeons = Dungeon::all()->keyBy('key');

        foreach ($dungeons as $dungeon) {
            // Already exists, we no longer care
            if ($dungeon->floors->isNotEmpty()) {
                continue;
            }

            // Bit of a hack to get the correct translation key..
            $dungeonTranslationKey = explode('.', $dungeon->name)[2];
            $floorsTranslationKey  = sprintf('dungeons.%s.%s.floors', $dungeon->expansion->shortname, $dungeonTranslationKey);

            $translatedFloors = __($floorsTranslationKey, [], 'en_US');

            $this->info(sprintf('- %s', __($dungeon->name, [], 'en_US')));
            $index = 1;
            if (!is_array($translatedFloors)) {
                throw new \Exception(sprintf('Translated floors should be an array for %s', $dungeon->name));
            }

            foreach ($translatedFloors as $key => $value) {
                $floorKey = sprintf('%s.%s', $floorsTranslationKey, $key);

                $floorAttributes = [
                    'dungeon_id' => $dungeon->id,
                    'name'       => $floorKey,
                    'index'      => $index,
                    'default'    => $index === 1,
                ];

                $facade = '';
                if (__($floorKey, [], 'en_US') === __($dungeon->name, [], 'en_US') &&
                    $dungeon->floors->count() > 1) {
                    $floorAttributes['facade']        = 1;
                    $floorAttributes['mdt_sub_level'] = 1;
                    $floorAttributes['ui_map_id']     = 0;
                    $facade                           = ' facade';
                }
                Floor::create($floorAttributes);

                $this->comment(sprintf('-- Added new%s floor %s', $facade, __($floorKey, [], 'en_US')));

                $index++;
            }
        }

        return 0;
    }
}
