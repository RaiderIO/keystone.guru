<?php

namespace App\Service\Wago;

interface WagoToolsServiceInterface
{
    /**
     * Resolves interface texture FileDataIDs - the `info.texture` MDT puts on its map POIs - to the icon file
     * names they are known by on Wowhead's CDN, e.g. 4620673 => `ui_profession_engineering`.
     *
     * FileDataIDs that are not an interface icon are absent from the result.
     *
     * @param  array<int>         $fileDataIds
     * @return array<int, string> FileDataID => icon file name, without extension, lowercased.
     */
    public function getIconFileNamesByFileDataIds(array $fileDataIds): array;
}
