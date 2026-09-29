<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compendium\SpellCompendiumRequest;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Spell\NameColumnHandler;
use App\Logic\Datatables\SpellsDatatablesHandler;
use App\Models\Dungeon;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\Spell\SpellTuningChangeRepositoryInterface;
use App\Service\Compendium\SpellCompendiumServiceInterface;
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SpellCompendiumController extends Controller
{
    /**
     * The spell index without a dungeon in the URL - bounces to the canonical URL of the visitor's
     * context dungeon.
     *
     * Deliberately a 302: the target depends on the visitor's own context dungeon, so a permanent
     * redirect would get cached and pin one dungeon into every later visit.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('spell.compendium.index.dungeon', [
            'dungeon' => Dungeon::getUserOrDefaultDungeon(),
        ]);
    }

    public function indexDungeon(Dungeon $dungeon, DungeonServiceInterface $dungeonService): View
    {
        // The URL is the source of truth for which dungeon is being viewed - make it the context
        // dungeon as well, so the header's dungeon selection follows along (as on explore/heatmap)
        $dungeonService->setDungeonContext($dungeon, Auth::user());

        return view('compendium.spell.index', [
            'contextDungeon' => $dungeon,
            // HeaderComposer only injects this into the header view itself - the dungeon context
            // links this page overrides are built in the view, so it needs its own copy
            'gameVersionDungeons' => $dungeonService->getDungeonsForGameVersion(),
            // The dungeon filter's options are dungeon ids; navigating to another dungeon's page
            // needs their slugs
            'dungeonSlugsById' => Dungeon::query()->pluck('slug', 'id'),
        ]);
    }

    public function show(
        Spell                                $spell,
        SpellCompendiumServiceInterface      $spellCompendiumService,
        SpellTuningChangeRepositoryInterface $spellTuningChangeRepository,
        Request                              $request,
    ): View|RedirectResponse {
        if ($spell->hidden_on_map) {
            abort(404);
        }

        if (($request->route()->originalParameters()['spell'] ?? '') !== $spell->getRouteKey()) {
            return redirect(route('spell.compendium.show', $spell), 301);
        }

        $spell->load(['gameVersion', 'dungeons.expansion', 'characteristic', 'descriptionTranslation']);

        // type/characteristics/npcHealths are what the NPC links' hover tooltips read (#4096)
        $npcs = $spell->npcs()->with(['classification', 'dungeons', 'type', 'characteristics', 'npcHealths'])->get();

        return view('compendium.spell.show', [
            'spell'     => $spell,
            'npcs'      => $npcs,
            'eventFeed' => $spellCompendiumService->buildEventFeed($spell),
            // Newest build first (the repository orders by build), values in description order within one
            'tuningChangesByBuild' => $spellTuningChangeRepository->getForSpell($spell->id)->groupBy('to_build'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(SpellCompendiumRequest $request): array
    {
        $dungeon = $request->dungeon();

        $locale         = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');
        $dungeonName    = "COALESCE(NULLIF(dungeon_translations.translation, ''), dungeon_fallback_translations.translation)";

        $spells = Spell::query()
            ->selectRaw(sprintf(
                'spells.*, %s as name, GROUP_CONCAT(DISTINCT %s ORDER BY %s SEPARATOR ", ") AS dungeon_names',
                NameColumnHandler::NAME_EXPRESSION,
                $dungeonName,
                $dungeonName,
            ))
            // The "used by" column renders an NPC link per npc, each carrying its own hover tooltip
            // (#4096) - which reads these four relations
            // descriptionTranslation is what the spell's own tooltip reads in a non-English locale
            ->with(['descriptionTranslation', 'npcs', 'npcs.classification', 'npcs.type', 'npcs.characteristics', 'npcs.npcHealths'])
            ->afterQuery(static function (EloquentCollection $spells): EloquentCollection {
                // tooltip_data is deliberately not appended by default - see Npc::$appends
                foreach ($spells as $spell) {
                    /** @var Spell $spell */
                    $spell->npcs->each->append('tooltip_data');
                }

                return $spells;
            })
            ->leftJoin('spell_dungeons', 'spell_dungeons.spell_id', '=', 'spells.id')
            ->leftJoin('dungeons', 'spell_dungeons.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations as dungeon_translations', static function (JoinClause $clause) use ($locale) {
                $clause->on('dungeon_translations.key', '=', 'dungeons.name')
                    ->where('dungeon_translations.locale', '=', $locale);
            })
            ->leftJoin('translations as dungeon_fallback_translations', static function (JoinClause $clause) use ($fallbackLocale) {
                $clause->on('dungeon_fallback_translations.key', '=', 'dungeons.name')
                    ->where('dungeon_fallback_translations.locale', '=', $fallbackLocale);
            });

        NameColumnHandler::joinNameTranslations($spells, $locale, $fallbackLocale)
            ->where('spells.hidden_on_map', false)
            ->groupBy('spells.id')
            ->orderByRaw(NameColumnHandler::NAME_EXPRESSION);

        if ($dungeon !== null) {
            $spells->where('spell_dungeons.dungeon_id', $dungeon->id);
        }

        $datatablesHandler = new SpellsDatatablesHandler($request);

        return $datatablesHandler->setBuilder($spells)
            ->addColumnHandler([
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler, $dungeonName),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }
}
