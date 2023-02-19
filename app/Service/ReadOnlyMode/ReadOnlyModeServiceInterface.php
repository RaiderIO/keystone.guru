<?php

namespace App\Service\ReadOnlyMode;

interface ReadOnlyModeServiceInterface
{
    /**
     * @param bool $readOnly
     * @return bool
     */
    public function setReadOnly(bool $readOnly): bool;

    /**
     * @return bool
     */
    public function isReadOnly(): bool;
}
