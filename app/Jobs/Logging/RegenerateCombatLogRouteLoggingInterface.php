<?php

namespace App\Jobs\Logging;

interface RegenerateCombatLogRouteLoggingInterface
{
    public function handleStart(int $dungeonRouteId): void;

    public function handleDungeonRouteNotFound(): void;

    public function handleChallengeModeRunNotSet(): void;

    public function handleBody(string $body): void;

    public function handleRequestError(string $message): void;

    public function handleEnd(bool $result): void;
}
