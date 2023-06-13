<?php

namespace App\Logic\CombatLog\CombatEvents\Interfaces;

interface HasParameters
{
    public function setParameters(array $parameters): self;

    public function getParameterCount(): int;
}
