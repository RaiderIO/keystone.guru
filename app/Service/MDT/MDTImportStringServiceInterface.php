<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Logic\MDT\Exception\MDTStringParseException;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Support\Collection;

interface MDTImportStringServiceInterface
{
    public function getDecoded(): ?array;

    /**
     * @throws InvalidMDTDungeonException
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     * @throws CliWeakaurasParserNotFoundException
     */
    public function getDetails(Collection $warnings, Collection $errors): ImportStringDetails;

    public function getDungeonRoute(Collection $warnings, Collection $errors, bool $sandbox = false, bool $save = false, bool $importAsThisWeek = false): DungeonRoute;

    public function setEncodedString(string $encodedString): self;
}
