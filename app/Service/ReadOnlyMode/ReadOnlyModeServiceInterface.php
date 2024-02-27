<?php

namespace App\Service\ReadOnlyMode;

interface ReadOnlyModeServiceInterface
{
    public function setReadOnly(bool $readOnly): bool;

    public function isReadOnly(): bool;
}
