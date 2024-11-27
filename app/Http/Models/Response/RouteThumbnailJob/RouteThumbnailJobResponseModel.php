<?php

namespace App\Http\Models\Response\RouteThumbnailJob;

use App\Http\Models\Response\ResponseModel;

/**
 * @OA\Schema(schema="RouteThumbnailJob")
 */
class RouteThumbnailJobResponseModel extends ResponseModel
{
    /**
     * @OA\Property(example="69")
     */
    public int $id;

    /**
     * @OA\Property(example="MS4cR1S")
     */
    public string $publicKey;

    /**
     * @OA\Property(example="1",description="If the dungeon supports combined floors, the highest floor_index represents the floor with all combined floors.")
     */
    public int $floorIndex;

    /**
     * @OA\Property(enum={"queued", "completed", "expired", "error"})
     */
    public int $status;

    /**
     * @OA\Property(example="900")
     */
    public int $viewportWidth;

    /**
     * @OA\Property(example="600")
     */
    public int $viewportHeight;

    /**
     * @OA\Property(example="900")
     */
    public int $imageWidth;

    /**
     * @OA\Property(example="600")
     */
    public int $imageHeight;

    /**
     * @OA\Property(example="2.2")
     */
    public float $zoomLevel;

    /**
     * @OA\Property(example="90")
     */
    public int $quality;

    /**
     * @OA\Property(example="493")
     */
    public int $queueSize;

    /**
     * @OA\Property(example="2024-01-25T20:22:14.000000Z")
     */
    public string $estimatedCompletion;

    /**
     * @OA\Property(example="2025-01-25T20:22:14.000000Z")
     */
    public string $expiresAt;

    /**
     * @OA\Property(ref="#/components/schemas/RouteThumbnailJobLinks")
     */
    public RouteThumbnailJobLinksResponseModel $links;
}
