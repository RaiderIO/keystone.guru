<?php

namespace App\Console\Commands;

use App\Models\CombatLog\CombatLogEvent;
use App\Models\Dungeon;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataService;
use App\Service\ChallengeModeRunData\ChallengeModeRunDataServiceInterface;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
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
        ChallengeModeRunDataServiceInterface $challengeModeRunDataService
    ): int {
//        dd($combatLogEventService->getGridAggregation(
//            new CombatLogEventFilter(
//                Dungeon::find(69)
//            )
//        )->toArray());

//        $challengeModeRunDataService->convert();
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
