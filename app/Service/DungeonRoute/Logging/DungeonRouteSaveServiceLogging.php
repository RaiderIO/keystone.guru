<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\RollbarStructuredLogging;

class DungeonRouteSaveServiceLogging extends RollbarStructuredLogging implements DungeonRouteSaveServiceLoggingInterface
{
    public function saveStart(?int $dungeonRouteId, bool $isNew, int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveFailed(?int $dungeonRouteId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function saveTemplateCloneStart(int $dungeonRouteId, int $templateRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveTemplateCloneEnd(int $dungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function saveEnd(?int $dungeonRouteId, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function saveTemporaryStart(int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveTemporarySaveFailed(int $dungeonId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function saveTemporaryEnd(string $publicKey, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function cloneRouteStart(int $sourceDungeonRouteId, bool $unpublished): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function cloneRouteEnd(int $newDungeonRouteId, bool $thumbnailsCopied): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
