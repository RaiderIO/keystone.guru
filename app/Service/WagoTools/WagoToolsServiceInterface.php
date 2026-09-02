<?php

namespace App\Service\WagoTools;

use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use Generator;

/**
 * Reads the game client's DB2 tables from https://wago.tools, which publishes them as one CSV per
 * table per game build.
 */
interface WagoToolsServiceInterface
{
    /**
     * The most recent build wago.tools has data for, e.g. `12.1.0.69214`, or null when the build list
     * could not be read.
     *
     * @param string $product the CDN product to read builds for, e.g. `wow`, `wowt` or `wow_classic`
     */
    public function getLatestBuild(string $product): ?string;

    /**
     * Download a DB2 table's CSV for the given build, and return the path it was saved to. Repeat calls
     * for the same table and build re-use the file that is already on disk.
     *
     * @throws WagoToolsDownloadException
     */
    public function getTableCsvPath(string $table, string $build): string;

    /**
     * Yield the rows of a DB2 table's CSV one at a time, keyed by column name. These files run into tens
     * of megabytes, so they are deliberately never held in memory as a whole.
     *
     * @return Generator<int, array<string, string>>
     *
     * @throws WagoToolsDownloadException
     */
    public function readTable(string $table, string $build): Generator;

    /**
     * Resolves interface texture FileDataIDs - the `info.texture` MDT puts on its map POIs - to the icon
     * file names they are known by on Wowhead's CDN, e.g. 4620673 => `ui_profession_engineering`.
     *
     * FileDataIDs that are not an interface icon are absent from the result.
     *
     * @param  array<int>         $fileDataIds
     * @return array<int, string> FileDataID => icon file name, without extension, lowercased.
     *
     * @throws WagoToolsDownloadException
     */
    public function getIconFileNamesByFileDataIds(array $fileDataIds, string $build): array;
}
