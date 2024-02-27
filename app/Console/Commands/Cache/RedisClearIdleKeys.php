<?php

namespace App\Console\Commands\Cache;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisClearIdleKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:clearidlekeys {seconds=3600}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all idle keys in redis for Laravel Model Cache that have not been accessed in a specific time in seconds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $seconds = (int) $this->argument('seconds');

        // Only keys starting with this prefix may be cleaned up by this task, ex.
        // keystoneguru-live-cache:d8123999fdd7267f49290a1f2bb13d3b154b452a:f723072f44f1e4727b7ae26316f3d61dd3fe3d33
        // keystoneguru-live-cache:p79vfrAn4QazxHVtLb5s4LssQ5bi6ZaWGNTMOblt
        $keyWhitelistRegex = [
            sprintf('/keystoneguru-%s-cache:.{40}(?::.{40})*/', config('app.type')),
        ];

        Log::channel('scheduler')->info(sprintf("Clearing idle keys in redis that haven't been accessed in %d seconds", $seconds));
        $i = 0;
        $nextKey = 0;
        $deletedKeysCount = 0;

        do {
            $result = Redis::command('SCAN', [$nextKey]);

            $nextKey = (int) $result[0];

            $toDelete = [];
            foreach ($result[1] as $redisKey) {
                $output = [];
                foreach ($keyWhitelistRegex as $regex) {
                    if (preg_match($regex, (string) $redisKey, $output) !== false) {
                        $idleTime = Redis::command('OBJECT', ['idletime', $redisKey]);
                        if ($idleTime > $seconds) {
                            $toDelete[] = $redisKey;
                        }

                        break;
                    }
                }
            }

            if (! empty($toDelete)) {
                $count = count($toDelete);

                // https://redis.io/commands/del/
                if (Redis::command('DEL', $toDelete) === $count) {
                    $deletedKeysCount += $count;
                } else {
                    Log::channel('scheduler')->warning(sprintf('Failed to delete %d keys', $count));
                }
            }

            $i++;
            if ($i % 1000 === 0) {
                Log::channel('scheduler')->info(sprintf('Scan count %d... (deleted %d keys)', $i, $deletedKeysCount));
                $deletedKeysCount = 0;
            }
        } while ($nextKey > 0);

        Log::channel('scheduler')->info(sprintf('Finished (deleted %d keys)', $deletedKeysCount));

        return 0;
    }
}
