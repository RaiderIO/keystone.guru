<?php

namespace App\Service\Health;

interface HealthCheckServiceInterface
{
    public function isDatabaseReachable(): bool;

    public function isRedisReachable(): bool;
}
