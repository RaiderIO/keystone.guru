<?php

namespace App\Service\DungeonRoute\PDF;

use App\Models\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Com\Tecnick\Pdf\Tcpdf;

class PDFExportService implements PDFExportServiceInterface
{

    public function exportToPDF(ThumbnailServiceInterface $thumbnailService, DungeonRoute $dungeonRoute): void
    {
        $pdf = new Tcpdf();

        // Metadata
        $pdf->setCreator('Keystone.guru');
        $pdf->setAuthor('Keystone.guru PDF Exporter');
        $pdf->setSubject('Exported route on Keystone.guru');
        $pdf->setTitle($dungeonRoute->title);
        $pdf->setKeywords('TCPDF', 'tc-lib-pdf', 'keystone.guru');


        // Add fonts (needed?)
//        $bfont = $pdf->font->insert($pdf->pon, 'helvetica');
//        $bfont = $pdf->font->insert($pdf->pon, 'times', 'BI');

        // Add images
        foreach ($dungeonRoute->dungeon->floors as $floor) {
            if ($thumbnailService->hasThumbnailForFloor($dungeonRoute, $floor->index)) {
                $pdfPage = $pdf->page->add();

                $imageId  = $pdf->image->add($thumbnailService->getTargetFilePath($dungeonRoute, $floor->index));
                $imageOut = $pdf->image->getSetImage($imageId, 0, 0, 288, 192, $pdfPage['height']);

                $pdf->page->addContent($imageOut);
            }
        }

        file_put_contents('/tmp/pdf.pdf', $pdf->getOutPDFString());
    }
}
