<?php

namespace App\Http\Resources;

use App\User;
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
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
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
