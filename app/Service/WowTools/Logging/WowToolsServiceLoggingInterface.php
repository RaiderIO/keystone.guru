<?php

namespace App\Service\WowTools\Logging;

interface WowToolsServiceLoggingInterface
{
    public function getDisplayIdRequestStart(): void;

    public function getDisplayIdRequestError(string $error): void;

    public function getDisplayIdRequestResult(int $displayInfoId): void;

    public function getDisplayIdRequestResultUnableFindCreateDisplayInfoID(): void;

    public function getDisplayIdRequestEnd(): void;
}
