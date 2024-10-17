<?php

namespace App\Service\CombatLog\Dtos;

readonly class CombatLog
{
    public function __construct(
        private string $filePath
    ) {

    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
