<?php

namespace App\Service\Controller\Api\V1;

use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface APIDungeonRouteControllerServiceInterface
{
    /**
     * @param string   $publicKey
     * @param int|null $width
     * @param int|null $height
     * @param int|null $quality
     * @return Collection|DungeonRouteThumbnailJob[]
     */
    public function createThumbnails(
        string $publicKey,
        ?int   $width = null,
        ?int   $height = null,
        ?int   $quality = null
    ): Collection;
}
