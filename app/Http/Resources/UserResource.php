<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
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
            'name' => $this->name,
            'links' => [
                'view' => route('profile.view', ['user' => $this]),
                'avatar' => $this->iconfile?->getURL(),
            ],
        ];
    }
}
