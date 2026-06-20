<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\RollbarStructuredLogging;

class DungeonRouteSaveServiceLogging extends RollbarStructuredLogging implements DungeonRouteSaveServiceLoggingInterface
{
    public function saveFromRequestStart(?int $dungeonRouteId, bool $isNew, int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveFromRequestSaveFailed(?int $dungeonRouteId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function saveFromRequestTemplateCloneStart(int $dungeonRouteId, int $templateRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveFromRequestTemplateCloneEnd(int $dungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function saveFromRequestEnd(?int $dungeonRouteId, bool $result): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function saveTemporaryFromRequestStart(int $dungeonId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function saveTemporaryFromRequestSaveFailed(int $dungeonId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function saveTemporaryFromRequestEnd(string $publicKey, bool $result): void
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
