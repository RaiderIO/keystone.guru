<?php

namespace App\Service\Spell\Description\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellDescriptionImportServiceLogging extends StructuredLogging implements SpellDescriptionImportServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function importDescriptionsStart(string $product, string $build, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function importDescriptionsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function importDescriptionsUnknownBuild(string $product): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function importDescriptionsNoDescriptionsFound(string $product, string $build): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }
}
