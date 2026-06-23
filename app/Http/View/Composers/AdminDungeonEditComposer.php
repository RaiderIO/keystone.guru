<?php

namespace App\Http\View\Composers;

use App\Models\Dungeon;
use App\Service\Mapping\MappingServiceInterface;
use Illuminate\View\View;

readonly class AdminDungeonEditComposer implements ViewComposerInterface
{
    public function __construct(
        private MappingServiceInterface $mappingService,
    ) {
    }

    public function compose(View $view): void
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = $view->getData()['dungeon'] ?? null;
        $view->with('hasUnmergedMappingVersion', $dungeon && $this->mappingService->getDungeonsWithUnmergedMappingChanges()->has($dungeon->id));
    }
}
