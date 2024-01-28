<?php

namespace App\Http\Models\Request\Route;

/**
 * @OA\Schema(schema="RouteThumbnailRequest")
 */
class RouteThumbnailRequestModel
{
    /**
     * @OA\Property(minimum="192",maximum="1620",example="900")
     *
     * @var int|null
     */
    public ?int $width;

    /**
     * @OA\Property(minimum="128",maximum="1080",example="600")
     *
     * @var int|null
     */
    public ?int $height;

    /**
     * @OA\Property(minimum="1",maximum="5",example="2.2")
     *
     * @var float|null
     */
    public ?float $zoom_level;

    /**
     * @OA\Property(minimum="0",maximum="100",example="90")
     *
     * @var int|null
     */
    public ?int $quality;
}
