<?php

namespace App\Service\MDT\Logging;

interface MDTImportStringServiceLoggingInterface extends MDTBaseServiceLoggingInterface
{
    /**
     * @param array<string, mixed> $latLngWithFloor
     */
    public function parseObjectAfterConversionFloorStillOnFacade(array $latLngWithFloor): void;

    public function getDetailsStart(): void;

    public function getDetailsEnd(): void;

    public function getDungeonRouteStart(bool $sandbox, bool $save, bool $importAsThisWeek): void;

    public function getDungeonRouteEnd(): void;

    public function setEncodedStringEncodedString(string $encodedString): void;

    public function parseMdtNpcClonesInPullEnemyForcesNotFound(int $npcId): void;
}
