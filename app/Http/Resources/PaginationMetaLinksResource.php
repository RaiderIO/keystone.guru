<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(schema="PaginationMetaLinks")
 * @OA\Property(type="string",property="url",nullable=true,example="https://keystone.guru/api/v1/dungeon?page=1")
 * @OA\Property(type="string",property="label",example="Next &raquo;")
 * @OA\Property(type="boolean",property="active")
 */
class PaginationMetaLinksResource extends JsonResource
{
}
