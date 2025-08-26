<?php

namespace App\Service\Wowhead\Logging;

interface WowheadTranslationServiceLoggingInterface
{

    public function getDungeonNamesStart(): void;

    public function getDungeonNamesLocaleStart(string $locale): void;

    public function getDungeonNamesWowheadUrl(string $url): void;

    public function getDungeonNamesSetDungeonName(string $dungeon, string $localizedName): void;

    public function getDungeonNamesLocaleEnd(): void;

    public function getDungeonNamesEnd(): void;
}
