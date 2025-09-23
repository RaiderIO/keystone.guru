<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(schema="PaginationLinks")
 * @OA\Property(type="string",property="first",example="https://keystone.guru/api/v1/dungeon?page=1")
 * @OA\Property(type="string",property="last",example="https://keystone.guru/api/v1/dungeon?page=3")
 * @OA\Property(type="string",property="prev",nullable=true,example="https://keystone.guru/api/v1/dungeon?page=1")
 * @OA\Property(type="string",property="next",nullable=true,example="https://keystone.guru/api/v1/dungeon?page=2")
 */
class PaginationLinksResource extends JsonResource
{
}
