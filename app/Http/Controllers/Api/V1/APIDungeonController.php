<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dungeon\DungeonCollectionResource;
use App\Models\Dungeon;
use Request;

class APIDungeonController extends Controller
{
    /**
     * @param Request $request
     * @return DungeonCollectionResource
     */
    public function list(Request $request): DungeonCollectionResource
    {
        return new DungeonCollectionResource(
            Dungeon::active()->paginate(50)
        );
    }
}