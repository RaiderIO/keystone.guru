<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\SpellDescriptionImportResult;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Closure;

interface SpellDescriptionImportServiceInterface
{
    /**
     * Read the DB2 tables of a game build, render a description for every spell we know, and store both
     * the rendered text and the template it came from.
     *
     * @param string                       $product    the CDN product to read, e.g. `wow` for retail
     * @param string|null                  $build      a specific build, or null for the most recent one wago.tools has
     * @param Closure(int, int): void|null $onProgress called with the number of spells done and the total
     *
     * @throws WagoToolsDownloadException
     */
    public function importDescriptions(
        string   $product,
        int      $gameVersionId,
        ?string  $build = null,
        ?Closure $onProgress = null,
    ): ?SpellDescriptionImportResult;
}
