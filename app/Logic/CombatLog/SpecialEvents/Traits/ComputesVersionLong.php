<?php

namespace App\Logic\CombatLog\SpecialEvents\Traits;

trait ComputesVersionLong
{
    abstract protected function getVersionNumber(): int;

    abstract public function getBuildVersion(): string;

    public function getVersionLong(): int
    {
        [
            $major,
            $minor,
            $patch,
        ] = explode('.', $this->getBuildVersion());

        return ($this->getVersionNumber() * 1_000_000_000) +
            ((int)$major * 1_000_000) + ((int)$minor * 1_000) + (int)$patch;
    }
}
