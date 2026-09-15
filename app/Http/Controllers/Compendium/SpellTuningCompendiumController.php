<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The Compendium's "what changed in the last patch" page: every spell tuning change per client build,
 * newest build first - unscoped, or only the spells of one dungeon.
 */
class SpellTuningCompendiumController extends Controller
{
    private const int BUILDS_PER_PAGE = 5;

    /**
     * Every dungeon's changes. Unlike the activity feed this page does not bounce to a context dungeon:
     * scanning a whole build's changes in one place is what the page is for.
     */
    public function index(
        SpellTuningChangeRepositoryInterface $spellTuningChangeRepository,
        DungeonServiceInterface              $dungeonService,
    ): View {
        return $this->renderIndex($spellTuningChangeRepository, $dungeonService);
    }

    public function indexDungeon(
        Dungeon                              $dungeon,
        SpellTuningChangeRepositoryInterface $spellTuningChangeRepository,
        DungeonServiceInterface              $dungeonService,
    ): View {
        // The URL is the source of truth for which dungeon is being viewed - make it the context
        // dungeon as well, so the header's dungeon selection follows along (as on explore/heatmap)
        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return $this->renderIndex($spellTuningChangeRepository, $dungeonService, $dungeon);
    }

    private function renderIndex(
        SpellTuningChangeRepositoryInterface $spellTuningChangeRepository,
        DungeonServiceInterface              $dungeonService,
        ?Dungeon                             $dungeon = null,
    ): View {
        $gameVersion = GameVersion::getUserOrDefaultGameVersion();

        $builds         = $spellTuningChangeRepository->getBuilds($gameVersion->id, $dungeon, self::BUILDS_PER_PAGE);
        $changesByBuild = [];

        foreach ($builds->items() as $build) {
            $changesByBuild[$build['to_build']] = $spellTuningChangeRepository->getForBuild($gameVersion->id, $build['to_build'], $dungeon);
        }

        return view('compendium.tuning.index', [
            'contextDungeon' => $dungeon,
            'builds'         => $builds,
            'changesByBuild' => $changesByBuild,
            // HeaderComposer only injects this into the header view itself - the dungeon context
            // links this page overrides are built in the view, so it needs its own copy
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
        ]);
    }
}
