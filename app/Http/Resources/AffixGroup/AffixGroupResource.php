<?php

namespace App\Http\Resources\AffixGroup;

use App\Http\Resources\Affix\AffixResource;
use App\Models\AffixGroup\AffixGroup;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * @OA\Schema(schema="AffixGroup")
 * @OA\Property(property="affixes", type="array", @OA\Items(ref="#/components/schemas/Affix"))
 *
 * @mixin AffixGroup
 */
class AffixGroupResource extends JsonResource
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
            'expansion' => $this->expansion->shortname,
            'season'    => $this->season_id,
            'affixes'   => $this->affixes->map(
                static fn($affix) => new AffixResource($affix),
            )->toArray(),
        ];
    }
}
