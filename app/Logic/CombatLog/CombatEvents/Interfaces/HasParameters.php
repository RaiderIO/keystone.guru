<?php

namespace App\Logic\CombatLog\CombatEvents\Interfaces;

interface HasParameters
{
    /**
     * @param array<int, mixed> $parameters
     */
    public function setParameters(array $parameters): self;

    public function getParameterCount(): int;
}
