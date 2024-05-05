<?php

namespace App\Service\ChallengeModeRunData\Logging;

interface ChallengeModeRunDataServiceLoggingInterface
{
    public function convertStart(bool $translate): void;

    public function convertEnd(): void;

    public function convertChallengeModeRunDataStart(): void;

    public function convertChallengeModeRunDataUnableToFindDungeon(int $mapId): void;

    public function convertChallengeModeRunDefaultAttributes(array $defaultAttributes): void;

    public function convertChallengeModeRunDataEnd(int $count): void;

    public function convertChallengeModeRunDataAndTranslateStart(): void;

    public function convertChallengeModeRunDataAndTranslateNoChallengeModeIdSet(): void;

    public function convertChallengeModeRunDataAndTranslateEnd(int $count): void;
}
