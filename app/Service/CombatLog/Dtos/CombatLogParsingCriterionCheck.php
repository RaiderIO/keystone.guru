<?php

namespace App\Service\CombatLog\Dtos;

class CombatLogParsingCriterionCheck
{
    public function __construct(
        private readonly string       $modelClass,
        private readonly int          $modelId,
        private readonly KeyLevelBand $band,
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

    public function getBand(): KeyLevelBand
    {
        return $this->band;
    }
}
