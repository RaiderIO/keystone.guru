<?php

namespace App\Http\Models\Request\Route;

/**
 * @OA\Schema(schema="RouteThumbnailRequest")
 */
class RouteThumbnailRequestModel
{
    /**
     * @OA\Property(minimum="768",maximum="1620",example="900")
     */
    public ?int $viewport_width = null;

    /**
     * @OA\Property(minimum="512",maximum="1080",example="600")
     */
    public ?int $viewport_height = null;

    /**
     * @OA\Property(minimum="192",maximum="1620",example="900")
     */
    public ?int $image_width = null;

    /**
     * @OA\Property(minimum="128",maximum="1080",example="600")
     */
    public ?int $image_height = null;

    /**
     * @OA\Property(minimum="1",maximum="5",example="2.2")
     */
    public ?float $zoom_level = null;

    /**
     * @OA\Property(minimum="0",maximum="100",example="90")
     */
    public ?int $quality = null;
}
