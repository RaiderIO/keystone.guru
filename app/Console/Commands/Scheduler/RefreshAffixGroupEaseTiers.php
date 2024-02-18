<?php

namespace App\Console\Commands\Scheduler;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\Dungeon;
use App\Models\SubcreationEaseTierPull;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Subcreation\Exceptions\InvalidResponseException;
use App\Service\Subcreation\SubcreationApiServiceInterface;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Console\Command;

class RefreshAffixGroupEaseTiers extends Command
{
    private const DUNGEON_NAME_MAPPING = [
        'Dawn of the Infinite: Galakrond\'s Fall' => 'DOTI: Galakrond\'s Fall',
        'Dawn of the Infinite: Murozond\'s Rise'  => 'DOTI: Murozond\'s Rise',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affixgroupeasetiers:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the affix group ease tiers from Subcreation.net';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param SubcreationApiServiceInterface $subcreationApiService
     * @param SeasonServiceInterface         $seasonService
     * @return int
     * @throws Exception
     */
    public function handle(SubcreationApiServiceInterface $subcreationApiService, SeasonServiceInterface $seasonService)
    {
        try {
            $tierLists = $subcreationApiService->getDungeonEaseTierListOverall();
        } catch (InvalidResponseException $exception) {
            $this->error(sprintf('Invalid response: %s', $exception->getMessage()));

            // @TODO fix when reimplementing Subcreation but as Archon instead.
            return 0;
        }

        if (!isset($tierLists['last_updated']) || !isset($tierLists['current_affixes']) || !isset($tierLists['source_url']) || !isset($tierLists['tier_lists'])) {
            $this->error(sprintf('Invalid response: %s', json_encode($tierLists)));

            return -1;
        }

        $lastUpdatedAt    = Carbon::createFromFormat('Y-m-d G:i:s.uP', $tierLists['last_updated']);
        $lastEaseTierPull = SubcreationEaseTierPull::latest()->first();

        if ($lastEaseTierPull === null || $lastUpdatedAt->isAfter($lastEaseTierPull->last_updated_at)) {
            $subcreationEaseTierPull = SubcreationEaseTierPull::create([
                'current_affixes' => $tierLists['current_affixes'],
                'source_url'      => $tierLists['source_url'],
                'last_updated_at' => $lastUpdatedAt->setTimezone(new DateTimeZone(config('app.timezone')))->toDateTimeString(),
            ]);

            $dungeonList = Dungeon::active()->get();
            $totalSaved  = 0;

            foreach ($tierLists['tier_lists'] as $affixString => $tierList) {
                $this->info(sprintf('- Parsing %s', $affixString));

                $affixGroupId = $this->getAffixGroupByString($seasonService, $affixString);

                // Only if we actually found an affix..
                if ($affixGroupId !== null) {
                    $this->info(sprintf('-- Parsing for AffixGroup %s (%s)', $affixString, $affixGroupId));
                    $saved = 0;

                    foreach ($tierList as $tier => $dungeons) {
                        foreach ($dungeons as $dungeonName) {
                            // If found
                            $dungeon = $dungeonList->first(function (Dungeon $dungeon) use ($dungeonName) {
                                // Translate the name of the dungeon to English (from a key), and then match it
                                $ksgDungeonName = __($dungeon->name, [], 'en-US');

                                return (self::DUNGEON_NAME_MAPPING[$ksgDungeonName] ?? $ksgDungeonName) === $dungeonName;
                            });

                            if ($dungeon instanceof Dungeon) {
                                (new AffixGroupEaseTier([
                                    'subcreation_ease_tier_pull_id' => $subcreationEaseTierPull->id,
                                    'affix_group_id'                => $affixGroupId,
                                    'dungeon_id'                    => $dungeon->id,
                                    'tier'                          => $tier,
                                ]))->save();

                                $saved++;
                                $totalSaved++;
                                $this->info(sprintf('--- Saved dungeon %s (%s)', $dungeonName, $dungeon->id));
                            } else {
                                $this->error(sprintf('--- Unknown dungeon %s', $dungeonName));
                            }
                        }
                    }

                    $this->info(sprintf('-- Saved %s ease tiers', $saved));
                } else {
                    $this->error(sprintf('-- Unable to find AffixGroup for affixes %s', $affixString));
                }
            }
        } else {
            $this->warn('Cannot update the Subcreation ease tier tiers - the data has not updated yet');
        }

        // Clear model cache so that it will be refreshed upon next request
        $this->call('modelCache:clear', ['--model' => AffixGroupEaseTier::class]);
        $this->call('modelCache:clear', ['--model' => SubcreationEaseTierPull::class]);

        return 0;
    }

    /**
     * @param SeasonServiceInterface $seasonService
     * @param string                 $affixString
     * @return int|null
     */
    private function getAffixGroupByString(SeasonServiceInterface $seasonService, string $affixString): ?int
    {
        $affixGroupId = null;

        $affixList                = Affix::all();
        $currentSeason            = $seasonService->getCurrentSeason();
        $currentSeasonAffixGroups = $currentSeason->affixgroups;

        $affixes = collect(explode(', ', $affixString));

        // Filter out properties that don't have the correct amount of affixes
        if ($affixes->count() === 3 + (int)($currentSeason->seasonal_affix_id !== null)) {
            // Check if there's any affixes in the list that we cannot find in our own database
            $invalidAffixes = $affixes->filter(function (string $affixName) use ($affixList) {
                // Find the affix in the list and match by translated name - must be found to continue
                return $affixList->filter(function (Affix $affix) use ($affixName) {
                    return __($affix->name, [], 'en-US') === $affixName;
                })->isEmpty();
            });

            // Non invalid affixes found, great!
            if ($invalidAffixes->isNotEmpty()) {
                $this->error(sprintf('Unable to find Affix(es) %s', $invalidAffixes->join(', ')));
            } // Cannot find an affix in this list - perhaps it's new?
            else {
                // Now we must find affixgroups that correspond to the affix list
                foreach ($currentSeasonAffixGroups as $affixGroup) {

                    // Loop over the affixes of the affix group and empty the list
                    $notFoundAffixes = $affixGroup->affixes->filter(function (Affix $affix) use ($affixes) {
                        return $affixes->search($affix->key) === false;
                    });

                    // If we have found the match, we're done
                    if ($notFoundAffixes->isEmpty()) {
                        $affixGroupId = $affixGroup->id;
                        break;
                    }
                }
            }
        }

        return $affixGroupId;
    }
}
