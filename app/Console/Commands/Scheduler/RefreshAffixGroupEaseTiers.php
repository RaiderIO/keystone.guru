<?php

namespace App\Console\Commands\Scheduler;

use App\Models\Affix;
use App\Models\AffixGroupEaseTier;
use App\Models\Dungeon;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Subcreation\SubcreationApiServiceInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RefreshAffixGroupEaseTiers extends Command
{
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
     * @param SeasonServiceInterface $seasonService
     * @return int
     * @throws \Exception
     */
    public function handle(SubcreationApiServiceInterface $subcreationApiService, SeasonServiceInterface $seasonService)
    {
        $tierLists = $subcreationApiService->getDungeonEaseTierListOverall();

        $dungeonList = Dungeon::active()->get();
        $startTime = Carbon::now();
        $totalSaved = 0;

        foreach ($tierLists['tier_lists'] as $affixString => $tierList) {
            $this->info(sprintf('Parsing %s', $affixString));

            $affixGroupId = $this->getAffixGroupByString($seasonService, $affixString);

            // Only if we actually found an affix..
            if ($affixGroupId !== null) {
                $this->info(sprintf('Parsing for AffixGroup %s', $affixGroupId));
                $saved = 0;

                foreach ($tierList as $tier => $dungeons) {
                    foreach ($dungeons as $dungeonName) {

                        // If found
                        $dungeon = $dungeonList->where('name', $dungeonName)->first();

                        if ($dungeon instanceof Dungeon) {
                            (new AffixGroupEaseTier([
                                'affix_group_id' => $affixGroupId,
                                'dungeon_id'     => $dungeon->id,
                                'tier'           => $tier
                            ]))->save();

                            $saved++;
                            $totalSaved++;
                        } else {
                            $this->error(sprintf('Unknown dungeon %s', $dungeonName));
                        }
                        break;
                    }
                }

                $this->info(sprintf('Saved %s ease tiers', $saved));
            } else {
                $this->error(sprintf('Unable to find Affixgroup for affixes %s', $affixString));
            }
        }

        // Only if we actually updated the table
        if ($totalSaved > 0) {
            $this->info($startTime->toDateTimeString());
            // Delete all old ease tiers
            $deleted = AffixGroupEaseTier::where('created_at', '<', $startTime)->delete();
            $this->info(sprintf('Deleted %s old tiers', $deleted));
        }

        return 0;
    }

    /**
     * @param SeasonServiceInterface $seasonService
     * @param string $affixString
     * @return int|null
     */
    private function getAffixGroupByString(SeasonServiceInterface $seasonService, string $affixString): ?int
    {
        $affixGroupId = null;

        $affixList = Affix::all();
        $currentSeasonAffixGroups = $seasonService->getCurrentSeason()->affixgroups;

        $affixes = collect(explode(', ', $affixString));
        // Filter out properties that don't have the correct amount of affixes
        if ($affixes->count() === 4) {
            // Check if there's any affixes in the list that we cannot find in our own database
            $invalidAffixes = $affixes->filter(function (string $affix) use ($affixList)
            {
                return $affixList->where('name', $affix)->isEmpty();
            });

            // None found, great!
            if ($invalidAffixes->isEmpty()) {

                // Now we must find affixgroups that correspond to the affix list
                foreach ($currentSeasonAffixGroups as $affixGroup) {

                    // Loop over the affixes of the affix group and empty the list
                    $notFoundAffixes = $affixGroup->affixes->filter(function (Affix $affix) use ($affixes)
                    {
                        return $affixes->search($affix->name) === false;
                    });

                    // If we have found the match, we're done
                    if ($notFoundAffixes->isEmpty()) {
                        $affixGroupId = $affixGroup->id;
                        break;
                    }
                }
            } // Cannot find an affix in this list - perhaps it's new?
            else {
                $this->error(sprintf('Unable to find Affix(es) %s', $invalidAffixes->join(', ')));
            }
        }

        return $affixGroupId;
    }
}
