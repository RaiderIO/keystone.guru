<?php

namespace App\Console\Commands;

use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Console\Command;

class Random extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'random';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(
        CombatLogEventServiceInterface       $combatLogEventService,
        ChallengeModeRunDataServiceInterface $challengeModeRunDataService,
        CoordinatesServiceInterface          $coordinatesService,
        SeasonServiceInterface               $seasonService,
        DungeonRouteRepositoryInterface      $dungeonRouteRepository
    ): int {

//        $dungeonRoute = $dungeonRouteRepository->find(1715);
//
//        $season = $dungeonRoute->getSeasonFromAffixes() ??
//            $seasonService->getMostRecentSeasonForDungeon($dungeonRoute->dungeon) ??
//            $seasonService->getSeasonAt($dungeonRoute->created_at);
//
//        if ($season?->hasDungeon($dungeonRoute->dungeon)) {
//            $this->info(sprintf('Would update season_id to %d', $season->id));
////            $dungeonRoute->update([
////                'season_id' => $season->id,
////            ]);
//        }
//
//        dd($dungeonRoute->id);

//        $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::find(1654);
//        $hallsOfInfusion          = Dungeon::firstWhere('key', Dungeon::DUNGEON_HALLS_OF_INFUSION);
//
//        $latLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
//            $hallsOfInfusion->currentMappingVersion,
//            $dungeonFloorSwitchMarker->getLatLng()
//        );
//
//        dd($latLng->toArrayWithFloor());

//        $combatLogEvents = $combatLogEventService->generateCombatLogEvents(
//            Season::findOrFail(13),
//            CombatLogEvent::EVENT_TYPE_PLAYER_DEATH,
//            1000,
//            10
//        );

//        dd($combatLogEventService->getAvailableDateRange(
//            new CombatLogEventFilter(
//                Dungeon::find(69)
//            )
//        ));

//        dd($combatLogEventService->getGridAggregation(
//            new CombatLogEventFilter(
//                Dungeon::find(69)
//            )
//        )->toArray());

        $challengeModeRunDataService->convert();
        $challengeModeRunDataService->insertAllToOpensearch();

//        $combatLogEvents = $combatLogEventService->getCombatLogEvents(
//            new CombatLogEventFilter(
//                Dungeon::find(69)
//            )
//        );
//
//        dd($combatLogEvents->count());

//        $structuredLoggingService->all();

//        dd($combatLogSplitService->splitCombatLogOnChallengeModes(
//            base_path('tests/Unit/App/Service/CombatLog/Fixtures/2_underrot/WoWCombatLog-051523_211651.zip')
//        ));

        //        (new UpdateDungeonRoutePopularity())->__invoke();

        //        dd($wowToolsService->getDisplayId(12345));

        //        $this->info(
        //            $expansionService->getCurrentAffixGroup(
        //                $expansionService->getCurrentExpansion(),
        //                GameServerRegion::getUserOrDefaultRegion()
        //            )
        //        );
        //
        //        $backupDir = config('keystoneguru.db_backup_dir');
        //        $this->info(
        //            sprintf('mysqldump -u %s -p\'%s\' %s | gzip -9 -c > %s/%s.%s.sql.gz',
        //                config('database.connections.migrate.username'),
        //                config('database.connections.migrate.password'),
        //                config('database.connections.migrate.database'),
        //                $backupDir,
        //                config('database.connections.migrate.database'),
        //                now()->format('Y.m.d-h.i')
        //            ),
        //        );

        // 'presence-local-route-edit.E2mXPo3'
        //        dd($echoServerHttpApiService->getStatus());
        //        dd($echoServerHttpApiService->getChannelInfo('presence-local-route-edit.E2mXPo3'));
        //        dd($echoServerHttpApiService->getChannelUsers('presence-local-route-edit.E2mXPo3'));
        //        dd($echoServerHttpApiService->getChannels());

        return 0;
    }
}
