<?php

namespace App\Http\View\Composers;

use App\Models\Dungeon;
use App\Service\Mapping\MappingServiceInterface;
use Illuminate\View\View;

class AdminDungeonEditComposer
{
    public function __construct(
        private readonly MappingServiceInterface $mappingService,
    ) {
    }

    public function compose(View $view): void
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = $view->getData()['dungeon'] ?? null;
        $view->with('hasUnmergedMappingVersion', $dungeon && $this->mappingService->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id));
    }
}
