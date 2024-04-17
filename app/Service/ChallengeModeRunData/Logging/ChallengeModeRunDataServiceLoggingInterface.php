<?php

namespace App\Service\ChallengeModeRunData\Logging;

interface ChallengeModeRunDataServiceLoggingInterface
{
    public function convertStart(): void;

    public function convertEnd(): void;

    public function convertChallengeModeRunDataStart(): void;

    public function convertChallengeModeRunDataUnableToFindDungeon(int $mapId): void;

    public function convertChallengeModeRunDefaultAttributes(array $defaultAttributes): void;

    public function convertChallengeModeRunDataEnd(int $count): void;
}
