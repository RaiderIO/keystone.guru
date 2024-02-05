<?php

namespace App\Http\Models\Response\RouteThumbnailJob;

/**
 * @OA\Schema(schema="RouteThumbnailJobLinks")
 */
class RouteThumbnailJobLinksResponseModel
{
    /**
     * @OA\Property(example="https://keystone.guru/api/v1/thumbnailJob/1")
     *
     * @var string
     */
    public string $status;

    /**
     * @OA\Property(example="https://keystone.guru/images/route_thumbnails_custom/MS4cR1S_1.jpg")
     *
     * @var string
     */
    public string $result;
}
