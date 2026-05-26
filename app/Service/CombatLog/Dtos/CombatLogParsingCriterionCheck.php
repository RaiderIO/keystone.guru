<?php

namespace App\Service\CombatLog\Dtos;

class CombatLogParsingCriterionCheck
{
    public function __construct(
        private readonly string $modelClass,
        private readonly int    $modelId,
    ) {
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }
}
