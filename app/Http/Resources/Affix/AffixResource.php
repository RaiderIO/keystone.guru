<?php

namespace App\Http\Resources\Affix;

use App\Models\Affix;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema ( schema="Affix" )
 * @OA\Property ( property="id", type="integer", example="9" )
 * @OA\Property ( property="name", type="string", example="Bolstering", description="The name of the affix in English" )
 *
 * @mixin Affix
 */
class AffixResource extends JsonResource
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
            'id'   => $this->affix_id,
            'name' => __($this->name, [], 'en_US'),
        ];
    }
}
