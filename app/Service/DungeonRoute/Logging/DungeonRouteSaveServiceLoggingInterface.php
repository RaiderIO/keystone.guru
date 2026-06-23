<?php

namespace App\Service\DungeonRoute\Logging;

interface DungeonRouteSaveServiceLoggingInterface
{
    public function saveStart(?int $dungeonRouteId, bool $isNew, int $dungeonId): void;

    public function saveFailed(?int $dungeonRouteId): void;

    public function saveTemplateCloneStart(int $dungeonRouteId, int $templateRouteId): void;

    public function saveTemplateCloneEnd(int $dungeonRouteId): void;

    public function saveEnd(?int $dungeonRouteId, bool $result): void;

    public function saveTemporaryStart(int $dungeonId): void;

    public function saveTemporarySaveFailed(int $dungeonId): void;

    public function saveTemporaryEnd(string $publicKey, bool $result): void;

    public function cloneRouteStart(int $sourceDungeonRouteId, bool $unpublished): void;

    public function cloneRouteEnd(int $newDungeonRouteId, bool $thumbnailsCopied): void;
}
