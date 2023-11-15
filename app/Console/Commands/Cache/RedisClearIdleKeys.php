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
    protected $description = 'Clears all idle keys in redis that have not been accessed in a specific time in seconds';

    /**
     * Execute the console command.
     * @return int
     */
    public function handle(): int
    {
        $seconds = (int)$this->argument('seconds');

        Log::channel('scheduler')->info(sprintf('Clearing idle keys in redis that haven\'t been accessed in %d seconds', $seconds));
        $i                = 0;
        $nextKey          = 0;
        $deletedKeysCount = 0;

        do {
            $result = Redis::command('SCAN', [$nextKey]);

            $nextKey = (int)$result[0];

            $toDelete = [];
            foreach ($result[1] as $redisKey) {
                // Just to get an insight in what is stored here
                if ($i < 100) {
                    Log::channel('scheduler')->debug(sprintf('%d: %s (next: %d)', $i, $redisKey, $nextKey));
                }

//                if (strlen($redisKey) === 40 || Str::endsWith($redisKey, 'forever_ref')) {
//                }

                $idleTime = Redis::command('OBJECT', ['idletime', $redisKey]);
                if ($idleTime > $seconds) {
                    $toDelete[] = $redisKey;
                }
            }

            if (!empty($toDelete)) {
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
