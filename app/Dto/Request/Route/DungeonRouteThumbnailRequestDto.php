<?php

namespace App\Dto\Request\Route;

use App\Dto\Request\RequestDto;

/**
 * @OA\Schema(schema="RouteThumbnailRequest")
 */
class DungeonRouteThumbnailRequestDto extends RequestDto
{
    public function __construct()
    {
    }

    /**
     * @OA\Property(minimum="768",maximum="1620",example="900")
     */
    public ?int $viewportWidth = null;

    /**
     * @OA\Property(minimum="512",maximum="1080",example="600")
     */
    public ?int $viewportHeight = null;

    /**
     * @OA\Property(minimum="192",maximum="1620",example="900")
     */
    public ?int $imageWidth = null;

    /**
     * @OA\Property(minimum="128",maximum="1080",example="600")
     */
    public ?int $imageHeight = null;

    /**
     * @OA\Property(minimum="1",maximum="5",example="2.2")
     */
    public ?float $zoomLevel = null;

    /**
     * @OA\Property(minimum="0",maximum="100",example="90")
     */
    public ?int $quality = null;
}
