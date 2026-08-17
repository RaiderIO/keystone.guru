<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compendium\NpcCompendiumRequest;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Characteristic;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Service\Compendium\NpcCompendiumServiceInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NpcCompendiumController extends Controller
{
    /**
     * The NPC index without a dungeon in the URL - bounces to the canonical URL of the visitor's
     * context dungeon.
     *
     * Deliberately a 302: the target depends on the visitor's own context dungeon, so a permanent
     * redirect would get cached and pin one dungeon into every later visit.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('npc.compendium.index.dungeon', [
            'dungeon' => Dungeon::getUserOrDefaultDungeon(),
        ]);
    }

    public function indexDungeon(Dungeon $dungeon, DungeonServiceInterface $dungeonService): View
    {
        // The URL is the source of truth for which dungeon is being viewed - make it the context
        // dungeon as well, so the header's dungeon selection follows along (as on explore/heatmap)
        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('compendium.npc.index', [
            'contextDungeon' => $dungeon,
            // HeaderComposer only injects this into the header view itself - the dungeon context
            // links this page overrides are built in the view, so it needs its own copy
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
            // The dungeon filter's options are dungeon ids; navigating to another dungeon's page
            // needs their slugs
            'dungeonSlugsById' => Dungeon::query()->pluck('slug', 'id'),
        ]);
    }

    public function show(Npc $npc, NpcCompendiumServiceInterface $npcCompendiumService, Request $request): View|RedirectResponse
    {
        if (($request->route()->originalParameters()['npc'] ?? '') !== $npc->getRouteKey()) {
            return redirect(route('npc.compendium.show', $npc), 301);
        }

        $npc->load(['classification', 'type', 'dungeons.expansion', 'npcSpells', 'npcHealths', 'characteristics', 'spells']);

        $currentGameVersion = GameVersion::getUserOrDefaultGameVersion();

        /** @var NpcHealth|null $currentNpcHealth */
        $currentNpcHealth = $npc->npcHealths->firstWhere('game_version_id', $currentGameVersion->id);

        return view('compendium.npc.show', [
            'npc'                => $npc,
            'currentNpcHealth'   => $currentNpcHealth,
            'allCharacteristics' => Characteristic::orderBy('id')->get(),
            'eventFeed'          => $npcCompendiumService->buildEventFeed($npc),
        ]);
    }

    public function activityIndex(
        SeasonServiceInterface $seasonService,
    ): RedirectResponse {
        $dungeon = $this->getContextDungeonOrDefault($seasonService);

        if ($dungeon === null) {
            return redirect()->route('home');
        }

        return redirect()->route('compendium.activity', ['dungeon' => $dungeon]);
    }

    public function activity(
        Dungeon                       $dungeon,
        SeasonServiceInterface        $seasonService,
        NpcCompendiumServiceInterface $npcCompendiumService,
        DungeonServiceInterface       $dungeonService,
    ): View|RedirectResponse {
        $contextDungeon = $this->getContextDungeonOrDefault($seasonService, $dungeon);
        if ($contextDungeon === null) {
            return redirect()->route('home');
        } elseif ($contextDungeon->id !== $dungeon->id) {
            return redirect()->route('compendium.activity', ['dungeon' => $contextDungeon]);
        }

        // The URL is the source of truth for which dungeon is being viewed - make it the context
        // dungeon as well, so the header's dungeon selection follows along (as on explore/heatmap)
        $dungeonService->setDungeonContext($dungeon, Auth::user());

        $dates       = $npcCompendiumService->getActivityDates(10, $dungeon);
        $eventsByDay = [];

        foreach ($dates->items() as $date) {
            $eventsByDay[$date] = $npcCompendiumService->getEventsForDate(Carbon::parse($date), $dungeon);
        }

        return view('compendium.activity.index', [
            'contextDungeon'      => $dungeon,
            'dates'               => $dates,
            'eventsByDay'         => $eventsByDay,
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
        ]);
    }

    public function activityDay(
        Dungeon                       $dungeon,
        string                        $date,
        NpcCompendiumServiceInterface $npcCompendiumService,
        DungeonServiceInterface       $dungeonService,
    ): View {
        try {
            $carbon = Carbon::createFromFormat('Y-m-d', $date);
        } catch (Exception) {
            abort(404);
        }

        if (!$carbon || $carbon->format('Y-m-d') !== $date) {
            abort(404);
        }

        // Deliberately no setDungeonContext() here, unlike every other dungeon-in-the-URL page: only
        // pages that validate their dungeon write the site-wide context, and this one does not.
        // activity() one route up rejects any dungeon outside the current season, so persisting one
        // from here would leave the visitor with a context its own overview refuses to show.

        return view('compendium.activity.day', [
            'contextDungeon'      => $dungeon,
            'date'                => $carbon,
            'events'              => $npcCompendiumService->getEventsForDate($carbon, $dungeon),
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(NpcCompendiumRequest $request): array
    {
        $mappingVersion = $request->dungeon()->getCurrentMappingVersion();

        $npcs = Npc::query()
            // The datatable renders the spells column off the serialized spells relation, and the
            // hover tooltip off the four relations behind tooltip_data (#4096)
            ->with(['spells', 'classification', 'type', 'characteristics', 'npcHealths'])
            // tooltip_data is not appended by default - it would land in the map context as well,
            // which renders no tooltips and would carry the text for nothing (see Npc::$appends)
            ->afterQuery(static fn(EloquentCollection $npcs): EloquentCollection => $npcs->each->append('tooltip_data'))
            ->selectRaw('npcs.*, npc_name_translations.translation as name, GROUP_CONCAT(DISTINCT dungeon_translations.translation SEPARATOR ", ") AS dungeon_names')
            ->join('enemies', 'enemies.npc_id', '=', 'npcs.id')
            ->join('mapping_versions', 'enemies.mapping_version_id', '=', 'mapping_versions.id')
            ->join('dungeons', 'mapping_versions.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations as dungeon_translations', static function (JoinClause $clause) {
                $clause->on('dungeon_translations.key', '=', 'dungeons.name')
                    ->where('dungeon_translations.locale', '=', 'en_US');
            })
            ->leftJoin('translations as npc_name_translations', static function (JoinClause $clause) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', 'en_US');
            })
            ->groupBy('npcs.id')
            ->orderBy('npcs.classification_id', 'DESC')
            ->orderBy('npc_name_translations.translation');

        if ($mappingVersion !== null) {
            $npcs->where('enemies.mapping_version_id', $mappingVersion->id);
        }

        $datatablesHandler = new NpcsDatatablesHandler($request);

        return $datatablesHandler->setBuilder($npcs)
            ->addColumnHandler([
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }

    private function getContextDungeonOrDefault(
        SeasonServiceInterface $seasonService,
        ?Dungeon               $dungeon = null,
    ): ?Dungeon {
        $result = null;

        $currentSeason = $seasonService->getCurrentSeason();

        if ($currentSeason !== null) {
            if ($dungeon !== null && $currentSeason->hasDungeon($dungeon)) {
                $result = $dungeon;
            } else {
                // Fall back on the context dungeon if the requested dungeon is not valid
                $contextDungeon = Dungeon::getUserOrDefaultDungeon();
                if ($currentSeason->hasDungeon($contextDungeon)) {
                    $result = $contextDungeon;
                } else {
                    /** @var Dungeon $dungeon */
                    $result = $currentSeason->dungeons()->first();
                }
            }
        }

        return $result;
    }
}
