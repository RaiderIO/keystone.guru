<?php

namespace App\Logic\CombatLog\Events\Interfaces;

interface HasParameters
{
    public function setParameters(array $parameters): self;

    public function getParameterCount(): int;
}
