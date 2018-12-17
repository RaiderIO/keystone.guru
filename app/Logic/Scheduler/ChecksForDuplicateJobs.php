<?php

namespace App\Logic\Scheduler;

trait ChecksForDuplicateJobs
{
    protected function isJobQueuedForModel($jobClassName, $model)
    {
        $exists = false;

        // Pass $exists by reference
        \DB::table(config('queue.connections.database.table'))->get()->each(function ($value, $key) use ($jobClassName, $model, &$exists) {
            // Decode the json stored in the payload
            $payload = json_decode($value->payload, true);

            // If the display name matches ours
            if ($payload['displayName'] === $jobClassName) {
                // Fetch the command object
                $obj = unserialize($payload['data']['command']);

                // If it exists already
                if ($exists = ($obj->model->id === $model->id)) {
                    // Break;
                    return false;
                }
            }

            return true;
        });

        return $exists;
    }
}