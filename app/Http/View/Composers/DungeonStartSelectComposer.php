<?php

namespace App\Http\View\Composers;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonStart;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class DungeonStartSelectComposer implements ViewComposerInterface
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $dungeonStartsByDungeonId = $this->viewService->getDungeonStartsByDungeonId();

        /** @var DungeonRoute|null $dungeonroute */
        $dungeonroute = $view->getData()['dungeonroute'] ?? null;

        // Edit mode: the route may use an older mapping version than the dungeon's current one, so source the
        // options from the route's own mapping version to ensure the stored value is present and selectable.
        if ($dungeonroute !== null) {
            $routeStarts = $dungeonroute->mappingVersion->dungeonStarts()
                ->get(['id', 'comment'])
                ->values()
                ->map(static fn(DungeonStart $dungeonStart, int $index) => [
                    'id'   => $dungeonStart->id,
                    'text' => $dungeonStart->getDisplayText($index),
                ]);

            if ($routeStarts->count() > 1) {
                $dungeonStartsByDungeonId = $dungeonStartsByDungeonId->put($dungeonroute->dungeon_id, $routeStarts);
            }
        }

        $view->with('dungeonStartsByDungeonId', $dungeonStartsByDungeonId);
    }
}
