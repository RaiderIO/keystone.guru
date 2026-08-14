<?php

namespace App\Service\Spell\Description\Logging;

interface SpellDescriptionImportServiceLoggingInterface
{
    public function importDescriptionsStart(string $product, string $build, int $gameVersionId): void;

    public function importDescriptionsEnd(): void;

    public function importDescriptionsUnknownBuild(string $product): void;

    public function importDescriptionsNoDescriptionsFound(string $product, string $build): void;
}
