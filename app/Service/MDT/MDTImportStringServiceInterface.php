<?php

namespace App\Service\MDT;

use App\Logic\MDT\Exception\CliWeakaurasParserNotFoundException;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTDungeonException;
use App\Logic\MDT\Exception\InvalidMDTStringException;
use App\Logic\MDT\Exception\MDTStringParseException;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Service\MDT\Models\ImportStringDetails;
use Illuminate\Support\Collection;

interface MDTImportStringServiceInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getDecoded(): ?array;

    /**
     * @param  Collection<int, ImportWarning>      $warnings
     * @param  Collection<int, ImportError>        $errors
     * @param  GameVersion|null                    $gameVersion The game version whose mapping the string is resolved
     *                                                          against; the acting user's when null.
     * @throws InvalidMDTDungeonException
     * @throws InvalidMDTStringException
     * @throws MDTStringParseException
     * @throws CliWeakaurasParserNotFoundException
     */
    public function getDetails(Collection $warnings, Collection $errors, ?GameVersion $gameVersion = null): ImportStringDetails;

    /**
     * @param Collection<int, ImportWarning> $warnings
     * @param Collection<int, ImportError>   $errors
     * @param GameVersion|null               $gameVersion The game version whose mapping the route is imported onto; the
     *                                                    acting user's when null.
     */
    public function getDungeonRoute(
        Collection   $warnings,
        Collection   $errors,
        bool         $sandbox = false,
        bool         $save = false,
        bool         $assignNotesToPulls = true,
        bool         $importAsThisWeek = false,
        ?GameVersion $gameVersion = null,
    ): DungeonRoute;

    public function setEncodedString(string $encodedString): self;
}
