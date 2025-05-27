<?php

namespace App\Service\ReadOnlyMode;

use App\Models\User;

interface ReadOnlyModeServiceInterface
{
    public function setReadOnly(bool $readOnly): bool;

    public function isReadOnly(): bool;

    public function isReadOnlyForUser(?User $user): bool;
}
