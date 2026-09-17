<?php

namespace App\Service\MDT\Export;

use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

interface MDTObjectExporterInterface
{
    /**
     * Builds this exporter's share of MDT's objects array. The objects are returned without an index -
     * MDTExportStringService numbers them across all exporters.
     *
     * @param  Collection<int, ImportWarning> $warnings
     * @return array<int, mixed>
     */
    public function export(DungeonRoute $dungeonRoute, Collection $warnings): array;
}
