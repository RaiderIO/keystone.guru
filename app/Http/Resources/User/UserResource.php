<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="User")
 * @OA\Property(property="publicKey",type="string",example="MS4cR1S")
 * @OA\Property(property="name",type="string",example="John Doe")
 * @OA\Property(property="links",type="object",ref="#/components/schemas/UserLinks")
 * @OA\Property(property="avatar",type="string",example="https://uploads.keystone.guru/uploads/5kf5hGB0jalkkxewRio6XEQP0KIaS9XJDFropTn7.png")
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
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'publicKey' => $this->public_key,
            'name'      => $this->name,
            'links'     => new UserLinksResource($this),
        ];
    }
}
