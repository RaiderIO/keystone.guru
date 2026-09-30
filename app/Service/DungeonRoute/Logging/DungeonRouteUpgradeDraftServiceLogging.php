<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class DungeonRouteUpgradeDraftServiceLogging extends StructuredLogging implements DungeonRouteUpgradeDraftServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function findOrCreateDraftStart(int $originalDungeonRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function findOrCreateDraftExistingDraftFound(int $originalDungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function findOrCreateDraftUpgradeFailed(int $originalDungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function findOrCreateDraftEnd(int $draftDungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function createDraftFromMdtStringStart(int $originalDungeonRouteId, bool $discardExistingDraft): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function createDraftFromMdtStringDiscardingExistingDraft(int $originalDungeonRouteId, int $draftDungeonRouteId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function createDraftFromMdtStringUpgradingMappingVersion(int $draftDungeonRouteId, int $mappingVersionId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function createDraftFromMdtStringFailed(int $originalDungeonRouteId, int $draftDungeonRouteId, string $message): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function createDraftFromMdtStringEnd(int $draftDungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function applyStart(int $draftDungeonRouteId, int $originalDungeonRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function applyPublishInvariantBypassed(int $draftDungeonRouteId, int $originalDungeonRouteId): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function applyEnd(int $originalDungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function discardStart(int $draftDungeonRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function discardEnd(int $draftDungeonRouteId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
