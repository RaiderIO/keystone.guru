<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\Guid\Guid;

/**
 * 8/14/2024 23:32:54.6402  STAGGER_CLEAR,Player-4184-00C9CE4F,81892.343750
 *
 * @author Wouter
 *
 * @since 21/08/2024
 */
class StaggerBase extends SpecialEvent
{

    private ?Guid $sourceGuid = null;

    private float $amount;

    public function getSourceGuid(): ?Guid
    {
        return $this->sourceGuid;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setParameters(array $parameters): self
    {
        parent::setParameters($parameters);

        $this->sourceGuid = Guid::createFromGuidString($parameters[0]);
        $this->amount     = (float)$parameters[1];

        return $this;
    }

    public function getParameterCount(): int
    {
        return 2;
    }
}
