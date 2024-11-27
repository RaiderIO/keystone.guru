<?php

namespace App\Http\Models\Response\RouteThumbnailJob;

use App\Http\Models\Response\ResponseModel;

/**
 * @OA\Schema(schema="RouteThumbnailJobLinks")
 */
class RouteThumbnailJobLinksResponseModel extends ResponseModel
{
    /**
     * @OA\Property(example="https://keystone.guru/api/v1/thumbnailJob/1")
     */
    public string $status;

    /**
     * @OA\Property(example="https://keystone.guru/images/route_thumbnails_custom/MS4cR1S_1.jpg")
     */
    public string $result;
}
