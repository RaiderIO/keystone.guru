<?php

namespace App\Http\Controllers\Api\V1\Public\Dungeon;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dungeon\DungeonEnvelopeResource;
use App\Http\Resources\Dungeon\DungeonResource;
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
    public function index(Request $request): DungeonEnvelopeResource
    {
        return new DungeonEnvelopeResource(
            Dungeon::active()->get(),
        );
    }

    /**
     * @OA\Get(
     *     operationId="getDungeon",
     *     path="/api/v1/dungeon/{dungeon}",
     *     summary="Get the details of a specific dungeon",
     *     tags={"Dungeon"},
     *
     *     @OA\Parameter(
     *         description="Slug of the dungeon you want to retrieve",
     *         in="path",
     *         name="dungeon",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *
     *
     *    @OA\Response(response=200, description="Successful operation",
     *        @OA\JsonContent(ref="#/components/schemas/DungeonWrap")
     *    )
     * )
     */
    public function show(Dungeon $dungeon): DungeonResource
    {
        return new DungeonResource(
            $dungeon,
        );
    }
}
