<?php

namespace App\Http\Models\Response\RouteThumbnailJob;

use App\Http\Models\Response\ResponseModel;

/**
 * @OA\Schema(schema="RouteThumbnailJobEnvelope")
 */
class RouteThumbnailJobEnvelopeResponseModel extends ResponseModel
{
    /**
     * @OA\Property(ref="#/components/schemas/RouteThumbnailJob")
     */
    public RouteThumbnailJobResponseModel $data;
}
