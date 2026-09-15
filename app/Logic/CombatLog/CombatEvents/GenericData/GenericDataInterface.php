<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

interface GenericDataInterface extends HasParameters
{
    public function getSourceGuidRaw(): string;

    public function getSourceGuid(): ?Guid;

    public function getSourceName(): string;

    public function getSourceFlags(): string;

    public function getSourceRaidFlags(): string;

    public function getDestGuidRaw(): string;

    public function getDestGuid(): ?Guid;

    public function getDestName(): string;

    public function getDestFlags(): string;

    public function getDestRaidFlags(): string;
}
