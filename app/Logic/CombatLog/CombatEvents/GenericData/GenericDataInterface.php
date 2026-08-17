<?php

namespace App\Logic\CombatLog\CombatEvents\GenericData;

use App\Logic\CombatLog\CombatEvents\Interfaces\HasParameters;
use App\Logic\CombatLog\Guid\Guid;

interface GenericDataInterface extends HasParameters
{
    /**
     * The unparsed source-GUID string. Unlike {@see getSourceGuid()}, this never triggers GUID parsing -
     * it is cheap to call on the hot ingest path to decide whether the parsed Guid is worth constructing
     * at all.
     */
    public function getSourceGuidRaw(): string;

    public function getSourceGuid(): ?Guid;

    public function getSourceName(): string;

    public function getSourceFlags(): string;

    public function getSourceRaidFlags(): string;

    /**
     * The unparsed dest-GUID string. Unlike {@see getDestGuid()}, this never triggers GUID parsing -
     * it is cheap to call on the hot ingest path to decide whether the parsed Guid is worth constructing
     * at all.
     */
    public function getDestGuidRaw(): string;

    public function getDestGuid(): ?Guid;

    public function getDestName(): string;

    public function getDestFlags(): string;

    public function getDestRaidFlags(): string;
}
