<?php

namespace App\Http\Resources\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Override;

/**
 * @OA\Schema(schema="CombatLogRouteEnemyFailureEnvelope")
 * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CombatLogRouteEnemyFailure"))
 * @OA\Property(property="meta", type="object",
 *     @OA\Property(property="count", type="integer", example=1000),
 *     @OA\Property(property="next_after_id", type="integer", nullable=true, example=12345, description="Pass as after_id to fetch the next page, null when this was the last page"),
 *     @OA\Property(property="has_more", type="boolean", example=true),
 * )
 */
class CombatLogRouteEnemyFailureEnvelopeResource extends ResourceCollection
{
    /**
     * @param Collection<int, CombatLogRouteEnemyFailure> $resource
     * @param array<int, string>                          $dungeonRoutePublicKeysById dungeon_route_id => public_key
     */
    public function __construct(
        Collection             $resource,
        private readonly bool  $hasMore,
        private readonly array $dungeonRoutePublicKeysById,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var CombatLogRouteEnemyFailure|null $last */
        $last = $this->collection->last();

        return [
            'data' => $this->collection->map(
                fn(CombatLogRouteEnemyFailure $failure) => new CombatLogRouteEnemyFailureResource(
                    $failure,
                    $failure->dungeon_route_id === null ? null : ($this->dungeonRoutePublicKeysById[$failure->dungeon_route_id] ?? null),
                ),
            )->toArray(),
            'meta' => [
                'count'         => $this->collection->count(),
                'next_after_id' => $this->hasMore && $last !== null ? $last->id : null,
                'has_more'      => $this->hasMore,
            ],
        ];
    }
}
