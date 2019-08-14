<?php

namespace App\Logic\Scheduler;

trait ChecksForDuplicateJobs
{
    protected function isJobQueuedForModel($jobClassName, $model, $queue = '')
    {
        $exists = false;

        /** @var \Illuminate\Redis\RedisManager $redis */
        $redis = \Queue::getRedis();

        $jobs = $redis->connection(null)->lrange(\Queue::getQueue($queue), 0, -1);
        // Pass $exists by reference
        foreach ($jobs as $jobJson) {
            $job = \GuzzleHttp\json_decode($jobJson, true);

            // If the display name matches ours
            if ($job['displayName'] === $jobClassName) {
                // Fetch the command object
                $obj = unserialize($job['data']['command']);

                // If it exists already
                if ($exists = ($obj->model->id === $model->id)) {
                    break;
                }
            }
        }

        return $exists;
    }
}