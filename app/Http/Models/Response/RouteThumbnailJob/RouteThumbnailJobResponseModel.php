<?php

namespace App\Http\Models\Response\RouteThumbnailJob;

/**
 * @OA\Schema(schema="RouteThumbnailJob")
 */
class RouteThumbnailJobResponseModel
{
    /**
     * @OA\Property(example="69")
     */
    public int $id;

    /**
     * @OA\Property(example="MS4cR1S")
     */
    public string $public_key;

    /**
     * @OA\Property(example="1",description="If the dungeon supports combined floors, the highest floor_index represents the floor with all combined floors.")
     */
    public int $floor_index;

    /**
     * @OA\Property(enum={"queued", "completed", "expired", "error"})
     */
    public int $status;

    /**
     * @OA\Property(example="900")
     */
    public int $viewport_width;

    /**
     * @OA\Property(example="600")
     */
    public int $viewport_height;

    /**
     * @OA\Property(example="900")
     */
    public int $image_width;

    /**
     * @OA\Property(example="600")
     */
    public int $image_height;

    /**
     * @OA\Property(example="2.2")
     */
    public float $zoom_level;

    /**
     * @OA\Property(example="90")
     */
    public int $quality;

    /**
     * @OA\Property(example="493")
     */
    public int $queue_size;

    /**
     * @OA\Property(example="2024-01-25T20:22:14.000000Z")
     */
    public string $estimated_completion;

    /**
     * @OA\Property(example="2025-01-25T20:22:14.000000Z")
     */
    public string $expires_at;

    /**
     * @OA\Property(ref="#/components/schemas/RouteThumbnailJobLinks")
     */
    public RouteThumbnailJobLinksResponseModel $links;
}
