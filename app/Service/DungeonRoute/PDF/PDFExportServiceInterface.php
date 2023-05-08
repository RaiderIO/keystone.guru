<?php

namespace App\Service\DungeonRoute\PDF;

use App\Models\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;

interface PDFExportServiceInterface
{
    public function exportToPDF(ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute): void;
}
