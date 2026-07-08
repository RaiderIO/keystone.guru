<?php

namespace App\Service\WowTools\Logging;

use Closure;

interface WowToolsServiceLoggingInterface
{
    /**
     * Wraps $callback in a getDisplayIdRequestStart/End pair, guaranteeing balanced logs even when the callback throws.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    public function getDisplayIdRequest(int $npcId, Closure $callback): mixed;

    public function getDisplayIdInvalidResponse(): void;

    public function getDisplayIdRequestError(string $error): void;

    public function getDisplayIdRequestResult(int $displayInfoId): void;

    public function getDisplayIdRequestResultUnableFindCreateDisplayInfoID(): void;
}
