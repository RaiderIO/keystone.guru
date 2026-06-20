<?php

namespace App\Service\DungeonRoute\Logging;

interface DungeonRouteSaveServiceLoggingInterface
{
    public function saveFromRequestStart(?int $dungeonRouteId, bool $isNew, int $dungeonId): void;

    public function saveFromRequestSaveFailed(?int $dungeonRouteId): void;

    public function saveFromRequestTemplateCloneStart(int $dungeonRouteId, int $templateRouteId): void;

    public function saveFromRequestTemplateCloneEnd(int $dungeonRouteId): void;

    public function saveFromRequestEnd(?int $dungeonRouteId, bool $result): void;

    public function saveTemporaryFromRequestStart(int $dungeonId): void;

    public function saveTemporaryFromRequestSaveFailed(int $dungeonId): void;

    public function saveTemporaryFromRequestEnd(string $publicKey, bool $result): void;

    public function cloneRouteStart(int $sourceDungeonRouteId, bool $unpublished): void;

    public function cloneRouteEnd(int $newDungeonRouteId, bool $thumbnailsCopied): void;
}
