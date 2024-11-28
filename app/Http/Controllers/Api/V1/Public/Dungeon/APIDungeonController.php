<?php

namespace App\Http\Controllers\Api\V1\Public\Dungeon;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dungeon\DungeonCollectionResource;
use App\Models\Dungeon;
use Request;

class APIDungeonController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getDungeons",
     *     path="/api/v1/dungeon",
     *     summary="Get a list of dungeons",
     *     tags={"Dungeon"},
     *
     *
     *     @OA\Response(response=200, description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/DungeonEnvelope"))
     *    )
     * )
     */
    public function get(Request $request): DungeonCollectionResource
    {
        return new DungeonCollectionResource(
            Dungeon::active()->paginate(50)
        );
    }
}
