<?php

namespace App\Jobs;

use App\Jobs\Logging\RegenerateCombatLogRouteLoggingInterface;
use App\Models\DungeonRoute\DungeonRoute;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateCombatLogRoute implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public function __construct(private readonly int $dungeonRouteId)
    {
        $this->queue = sprintf('%s-%s-long-running', config('app.type'), config('app.env'));
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var RegenerateCombatLogRouteLoggingInterface $log */
        $log    = app()->make(RegenerateCombatLogRouteLoggingInterface::class);
        $result = false;

        try {
            $log->handleStart($this->dungeonRouteId);

            $dungeonRoute = DungeonRoute::with(['challengeModeRun', 'challengeModeRun.challengeModeRunData'])->find($this->dungeonRouteId);

            if ($dungeonRoute === null) {
                $log->handleDungeonRouteNotFound();

                return;
            }

            $challengeModeRun = $dungeonRoute->challengeModeRun;
            if ($challengeModeRun === null || $challengeModeRun->challengeModeRunData === null) {
                $log->handleChallengeModeRunNotSet();

                return;
            }

            $client = new Client();

            try {
                $bodyArr = json_decode($challengeModeRun->challengeModeRunData->post_body, true);
                // Make sure we're regenerating this route!
                $bodyArr['settings']['publicKey'] = $dungeonRoute->public_key;
                $log->handleBody(json_encode($bodyArr));

                $client->post(route('api.v1.combatlog.route.store'), [
                    'auth' => [
                        config('keystoneguru.combat_log_route_regeneration.user'),
                        config('keystoneguru.combat_log_route_regeneration.password'),
                    ],
                    'body'    => json_encode($bodyArr),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);

                $result = true;
            } catch (\Throwable $e) {
                $log->handleRequestError($e->getMessage());
            }
        } finally {
            $log->handleEnd($result);
        }
    }
}
