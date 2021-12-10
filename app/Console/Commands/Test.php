<?php

namespace App\Console\Commands;

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
    public function handle(TimewalkingEventServiceInterface $timewalkingEventService)
    {
        $affixGroup = $timewalkingEventService->getTimewalkingEventAffixGroupAt(now()->addWeeks(2));
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
