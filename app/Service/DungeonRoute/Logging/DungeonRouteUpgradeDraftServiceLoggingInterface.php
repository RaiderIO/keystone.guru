<?php

namespace App\Service\DungeonRoute\Logging;

interface DungeonRouteUpgradeDraftServiceLoggingInterface
{
    public function findOrCreateDraftStart(int $originalDungeonRouteId): void;

    public function findOrCreateDraftExistingDraftFound(int $originalDungeonRouteId, int $draftDungeonRouteId): void;

    public function findOrCreateDraftUpgradeFailed(int $originalDungeonRouteId, int $draftDungeonRouteId): void;

    public function findOrCreateDraftEnd(int $draftDungeonRouteId): void;

    public function createDraftFromMdtStringStart(int $originalDungeonRouteId, ?int $discardExistingDraftId): void;

    public function createDraftFromMdtStringDiscardingExistingDraft(int $originalDungeonRouteId, int $draftDungeonRouteId): void;

    public function createDraftFromMdtStringUpgradingMappingVersion(int $draftDungeonRouteId, int $mappingVersionId): void;

    public function createDraftFromMdtStringFailed(int $originalDungeonRouteId, int $draftDungeonRouteId, string $message): void;

    public function createDraftFromMdtStringEnd(int $draftDungeonRouteId): void;

    public function applyStart(int $draftDungeonRouteId, int $originalDungeonRouteId): void;

    public function applyPublishInvariantBypassed(int $draftDungeonRouteId, int $originalDungeonRouteId): void;

    public function applyEnd(int $originalDungeonRouteId): void;

    public function discardStart(int $draftDungeonRouteId): void;

    public function discardEnd(int $draftDungeonRouteId): void;
}
