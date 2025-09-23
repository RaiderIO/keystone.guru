<?php

namespace App\Service\MDT\Logging;

use App\Logging\StructuredLogging;

class MDTImportStringServiceLogging extends StructuredLogging implements MDTImportStringServiceLoggingInterface
{
    public function parseObjectCommentAfterConversionFloorStillOnFacade(array $latLngWithFloor): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function getDetailsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function getDetailsEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function getDungeonRouteStart(bool $sandbox, bool $save, bool $importAsThisWeek): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function getDungeonRouteEnd(): void
    {
        $this->end(__METHOD__);
    }

    public function setEncodedStringEncodedString(string $encodedString): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
