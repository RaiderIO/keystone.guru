<?php

namespace App\Service\ChallengeModeRunData\Logging;

interface ChallengeModeRunDataServiceLoggingInterface
{
    public function convertStart(): void;

    public function convertEnd(): void;

    public function convertChallengeModeRunDataStart(): void;

    public function convertChallengeModeRunDataNoChallengeModeIdSet(): void;

    public function convertChallengeModeRunDataEnd(int $count): void;
}
