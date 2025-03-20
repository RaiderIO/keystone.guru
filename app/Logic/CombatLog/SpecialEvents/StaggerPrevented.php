<?php

namespace App\Logic\CombatLog\SpecialEvents;

/**
 * 8/27/2024 20:59:34.5502  STAGGER_PREVENTED,Player-1084-0A912E3E,76280.429688
 * Retail 11.1.0 and up?
 * 3/11/2025 19:41:15.9521  STAGGER_PREVENTED,Player-1084-0A912E3E,451253,171505.781250
 *
 * @author Wouter
 *
 * @since 21/08/2024
 */
class StaggerPrevented extends StaggerBase
{
    public function getParameterCount(): int
    {
        return 3;
    }

    public function getOptionalParameterCount(): int
    {
        return 1;
    }
}
