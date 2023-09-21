<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

interface GenericDataInterface extends HasParameters
{
    /**
     * @return Guid|null
     */
    public function getSourceGuid(): ?Guid;

    /**
     * @return string
     */
    public function getSourceName(): string;

    /**
     * @return string
     */
    public function getSourceFlags(): string;

    /**
     * @return string
     */
    public function getSourceRaidFlags(): string;

    /**
     * @return Guid|null
     */
    public function getDestGuid(): ?Guid;

    /**
     * @return string
     */
    public function getDestName(): string;

    /**
     * @return string
     */
    public function getDestFlags(): string;

    /**
     * @return string
     */
    public function getDestRaidFlags(): string;
}
