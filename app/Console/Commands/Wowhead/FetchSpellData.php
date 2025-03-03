<?php

namespace App\Console\Commands\Wowhead;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Spell\Spell;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FetchSpellData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:fetchspelldata {--dungeon=} {--spellId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches spell data from Wowhead that is missing from the database.';

    /**
     * Execute the console command.
     *
     *
     * @throws Exception
     */
    public function handle(WowheadServiceInterface $wowheadService): void
    {
        $dungeonKey = $this->option('dungeon');
        $spellId    = (int)$this->option('spellId');

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        if ($dungeonKey !== null) {
            $dungeon = Dungeon::where('key', $dungeonKey)->firstOrFail();

            $spells = $dungeon->spells;
        } else if ($spellId > 0) {
            $spells = collect([Spell::findOrFail($spellId)]);
        } else {
            $spells = Spell::whereNull('fetched_data_at')
                ->orWhere('fetched_data_at', '<=', Carbon::now()->subDay())
                ->get();
        }

        // If it's just one.. whatever
        if ($spells->count() > 1) {
            $this->info(sprintf('Fetching spell data for %d spells', $spells->count()));
        }

        $gameVersions = [];
        if ($dungeon?->gameVersion !== null) {
            $gameVersions[] = $dungeon->gameVersion;
        } else {
            // Check both retail and classic era
            $gameVersions = GameVersion::whereIn('id', [
                GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
                GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA],
            ])->get();
        }

        $i = 1;
        foreach ($spells as $spell) {
            if ($spells->count() > 1) {
                $this->info(sprintf('Fetching spell data for spell %d (%d/%d)', $spell->id, $i, $spells->count()));
            } else {
                $this->info(sprintf('Fetching spell data for spell %d', $spell->id));
            }

            foreach ($gameVersions as $gameVersion) {
                $spellDataResult = $wowheadService->getSpellData($gameVersion, $spell->id);

                if ($spellDataResult === null) {
                    $this->warn(sprintf('- Unable to find spell data for spell (%s)!', __($gameVersion->name, [], 'en_US')));
                } else {
                    $spellAttributes                    = $spellDataResult->toArray();
                    $spellAttributes['fetched_data_at'] = Carbon::now();
                    $spell->update($spellAttributes);

                    $this->info(sprintf('- %s', $spellDataResult->getName()));
                    foreach (array_filter($spellAttributes) as $key => $value) {
                        $this->comment(sprintf('-- %s: %s', $key, $value));
                    }
                    break;
                }
            }

            $i++;

            // Don't DDOS
//            usleep(500000);
        }
    }
}
