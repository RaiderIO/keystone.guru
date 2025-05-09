<?php

namespace App\Console\Commands\Traits;

use InfluxDB\Database;
use InfluxDB\Exception;
use InfluxDB\Point;
use TrayLabs\InfluxDB\Facades\InfluxDB;

trait SavesToInfluxDB
{
    /**
     * @throws Exception
     * @throws Database\Exception
     */
    public function savePointToInfluxDB(string $measurement, array $tags, array $fields, int $timestamp = null): void
    {
        if (config('influxdb.enabled')) {
            $tags = array_merge($this->getTags(), $tags);

            $point = new Point($measurement, null, $tags, $fields, $timestamp ?? time());

            InfluxDB::writePoints([$point], Database::PRECISION_SECONDS);
        }
    }

    protected function getTags()
    {
        return config('keystoneguru.influxdb.default_tags');
    }
}
