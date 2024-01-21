<?php

namespace App\Service\Controller\Api\V1;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Collection;

class APIDungeonRouteControllerService implements APIDungeonRouteControllerServiceInterface
{

    private ThumbnailServiceInterface $thumbnailService;

    /**
     * @param ThumbnailServiceInterface $thumbnailService
     */
    public function __construct(ThumbnailServiceInterface $thumbnailService)
    {
        $this->thumbnailService = $thumbnailService;
    }

    /**
     * @param string   $publicKey
     * @param int|null $width
     * @param int|null $height
     * @param int|null $quality
     * @return Collection|DungeonRouteThumbnailJob[]
     */
    public function createThumbnails(string $publicKey, ?int $width = null, ?int $height = null, ?int $quality = null): Collection
    {
        $result = collect();

        $dungeonRoute = DungeonRoute::where('public_key', $publicKey)->firstOrFail();

        $this->thumbnailService->queueThumbnailRefresh($dungeonRoute);

        return $result;
    }
}
