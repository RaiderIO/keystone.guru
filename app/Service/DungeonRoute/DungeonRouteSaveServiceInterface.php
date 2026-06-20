<?php

namespace App\Service\DungeonRoute;

use App\Http\Requests\DungeonRoute\DungeonRouteSubmitTemporaryFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Foundation\Http\FormRequest;

interface DungeonRouteSaveServiceInterface
{
    public function saveFromRequest(DungeonRoute $dungeonRoute, FormRequest $request): bool;

    public function saveTemporaryFromRequest(
        DungeonRoute                           $dungeonRoute,
        DungeonRouteSubmitTemporaryFormRequest $request,
    ): bool;

    public function cloneRoute(DungeonRoute $source, bool $unpublished = true): DungeonRoute;
}
