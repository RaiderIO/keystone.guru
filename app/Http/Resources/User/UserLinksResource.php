<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(schema="UserLinks")
 * @OA\Property(property="view", type="string", example="https://keystone.guru/profile/1")
 * @OA\Property(property="avatar", type="string", example="https://keystone.guru/storage/uploads/myavatar.jpg")
 *
 * @mixin User
 */
class UserLinksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'view'   => route('profile.view', ['user' => $this]),
            'avatar' => $this->iconfile?->getURL(),
        ];
    }
}
