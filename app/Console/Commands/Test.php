<?php

namespace App\Console\Commands;

use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Expansion;
use App\Service\Expansion\ExpansionService;
use App\Service\TimewalkingEvent\TimewalkingEventServiceInterface;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle(ExpansionService $expansionService, TimewalkingEventServiceInterface $timewalkingEventService)
    {
        $legionExpansionId = Expansion::where('shortname', Expansion::EXPANSION_LEGION)->first()->id;

        $result = DungeonRoute::select('dungeon_routes.*')
            ->where('dungeons.expansion_id', $legionExpansionId)
            ->join('dungeons', 'dungeons.id', 'dungeon_routes.dungeon_id')
            ->get();

        foreach($result as $dungeonRoute){
            DungeonRouteAffixGroup::where('dungeon_route_id', $dungeonRoute->id)->delete();

            logger()->debug(sprintf('Deleted affixes for dungeon route %d', $dungeonRoute->id));

            // Give the dungeon route new affix groups
            DungeonRouteAffixGroup::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'affix_group_id' => 73
            ]);
            logger()->debug(sprintf('Created new affixes for dungeon route %d', $dungeonRoute->id));
        }

        dd($result->pluck('id'));

        $affixGroup = $timewalkingEventService->getAffixGroupAt(now()->addWeeks(2));
//        dd(optional($affixGroup)->getTextAttribute());
//        dd($timewalkingEventService->getActiveTimewalkingEventAt(now()->addWeeks(14)));
        // 'presence-local-route-edit.E2mXPo3'
//        dd($echoServerHttpApiService->getStatus());
//        dd($echoServerHttpApiService->getChannelInfo('presence-local-route-edit.E2mXPo3'));
//        dd($echoServerHttpApiService->getChannelUsers('presence-local-route-edit.E2mXPo3'));
//        dd($echoServerHttpApiService->getChannels());

        return 0;
    }
}
