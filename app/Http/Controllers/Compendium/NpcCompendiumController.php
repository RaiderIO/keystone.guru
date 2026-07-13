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
use App\Service\Season\SeasonServiceInterface;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NpcCompendiumController extends Controller
{
    public function index(): View
    {
        return view('compendium.npc.index', [
            'contextDungeon' => Dungeon::getUserOrDefaultDungeon(),
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
    ): View|RedirectResponse {
        $contextDungeon = $this->getContextDungeonOrDefault($seasonService, $dungeon);
        if ($contextDungeon === null) {
            return redirect()->route('home');
        } elseif ($contextDungeon->id !== $dungeon->id) {
            return redirect()->route('compendium.activity', ['dungeon' => $contextDungeon]);
        }

        $dates       = $npcCompendiumService->getActivityDates(10, $dungeon);
        $eventsByDay = [];

        foreach ($dates->items() as $date) {
            $eventsByDay[$date] = $npcCompendiumService->getEventsForDate(Carbon::parse($date), $dungeon);
        }

        return view('compendium.activity.index', [
            'contextDungeon' => $dungeon,
            'dates'          => $dates,
            'eventsByDay'    => $eventsByDay,
        ]);
    }

    public function activityDay(Dungeon $dungeon, string $date, NpcCompendiumServiceInterface $npcCompendiumService): View
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m-d', $date);
        } catch (Exception) {
            abort(404);
        }

        if (!$carbon || $carbon->format('Y-m-d') !== $date) {
            abort(404);
        }

        return view('compendium.activity.day', [
            'contextDungeon' => $dungeon,
            'date'           => $carbon,
            'events'         => $npcCompendiumService->getEventsForDate($carbon, $dungeon),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(NpcCompendiumRequest $request): array
    {
        $mappingVersion = $request->dungeon()->getCurrentMappingVersion();

        $npcs = Npc::query()
            // The datatable renders the spells column off the serialized spells relation
            ->with(['spells'])
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
