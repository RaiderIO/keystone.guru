<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="User")
 * @OA\Property(property="public_key", type="string", example="MS4cR1S")
 * @OA\Property(property="name", type="string", example="John Doe")
 * @OA\Property(property="links", ref="#/components/schemas/UserLinks")
 * @OA\Property(property="avatar", type="string", example="https://keystone.guru/images/avatar/MS4cR1S.jpg")
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        return [
            'public_key' => $this->public_key,
            'name'       => $this->name,
            'links'      => new UserLinksResource($this),
        ];
    }
}