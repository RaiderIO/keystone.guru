<?php

namespace App\Service\MDT\Logging;

interface MDTImportStringServiceLoggingInterface
{
    public function getDetailsStart(): void;

    public function getDetailsEnd(): void;

    public function getDungeonRouteStart(bool $sandbox, bool $save, bool $importAsThisWeek): void;

    public function getDungeonRouteEnd(): void;

    public function setEncodedStringEncodedString(string $encodedString): void;
}
