<?php

namespace App\Service\Spell\Description\Logging;

interface SpellDescriptionImportServiceLoggingInterface
{
    public function importDescriptionsStart(string $product, string $build, int $gameVersionId): void;

    public function importDescriptionsEnd(): void;

    public function importDescriptionsUnknownBuild(string $product): void;

    public function importDescriptionsNoDescriptionsFound(string $product, string $build): void;

    public function persistPvpTalentFlagsTableUnavailable(string $build, string $message): void;

    public function persistPvpTalentFlagsNoRows(string $build): void;

    public function persistPvpTalentFlagsDone(string $build, int $pvpTalentSpellCount, int $flaggedCount, int $unflaggedCount): void;

    public function importTranslationsLocaleStart(string $locale): void;

    public function importTranslationsLocaleEmpty(string $locale): void;
}
