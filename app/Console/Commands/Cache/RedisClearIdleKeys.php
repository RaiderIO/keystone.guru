<?php

namespace App\Console\Commands\Cache;

use Illuminate\Console\Command;
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
     * @return int
     */
    public function handle()
    {
        $seconds = (int)$this->argument('seconds');

        $this->info(sprintf('Clearing idle keys in redis that haven\'t been accessed in %d seconds', $seconds));
        $i                = 0;
        $nextKey          = 0;
        $deletedKeysCount = 0;

        do {
            $result = Redis::command('SCAN', [$nextKey]);

            $nextKey = (int)$result[0];

            $toDelete = [];
            foreach ($result[1] as $redisKey) {
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
                    $this->warn(sprintf('Failed to delete %d keys', $count));
                }
            }

            $i++;
            if ($i % 1000 === 0) {
                $this->info(sprintf('Scan count %d... (deleted %d keys)', $i, $deletedKeysCount));
                $deletedKeysCount = 0;
            }
        } while ($nextKey > 0);

        $this->info(sprintf('Finished (deleted %d keys)', $deletedKeysCount));

        return 0;
    }
}
