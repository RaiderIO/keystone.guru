<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(schema="PaginationMeta")
 * @OA\Property(type="integer",property="current_page",example="1")
 * @OA\Property(type="integer",property="from",example="1")
 * @OA\Property(type="integer",property="last_page",example="5")
 * @OA\Property(type="string",property="path",example="https://keystone.guru/api/v1/dungeon")
 * @OA\Property(type="integer",property="per_page",example="50")
 * @OA\Property(type="integer",property="to",example="50")
 * @OA\Property(type="integer",property="total",example="121")
 * @OA\Property(type="array",property="links",@OA\Items(ref="#/components/schemas/PaginationMetaLinks"))
 *
 */
class PaginationMetaResource extends JsonResource
{
}
