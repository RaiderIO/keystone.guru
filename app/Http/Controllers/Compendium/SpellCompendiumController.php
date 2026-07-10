<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compendium\SpellCompendiumRequest;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Spell\NameColumnHandler;
use App\Logic\Datatables\SpellsDatatablesHandler;
use App\Models\Dungeon;
use App\Models\Spell\Spell;
use App\Service\Compendium\SpellCompendiumServiceInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpellCompendiumController extends Controller
{
    public function index(): View
    {
        return view('compendium.spell.index', [
            'contextDungeon' => Dungeon::getUserOrDefaultDungeon(),
        ]);
    }

    public function show(Spell $spell, SpellCompendiumServiceInterface $spellCompendiumService, Request $request): View|RedirectResponse
    {
        if ($spell->hidden_on_map) {
            abort(404);
        }

        if (($request->route()->originalParameters()['spell'] ?? '') !== $spell->getRouteKey()) {
            return redirect(route('spell.compendium.show', $spell), 301);
        }

        $spell->load(['gameVersion', 'dungeons.expansion', 'characteristic']);

        $npcs = $spell->npcs()->with(['classification', 'dungeons'])->get();

        return view('compendium.spell.show', [
            'spell'     => $spell,
            'npcs'      => $npcs,
            'eventFeed' => $spellCompendiumService->buildEventFeed($spell),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(SpellCompendiumRequest $request): array
    {
        $dungeon = $request->dungeon();

        $spells = Spell::query()
            ->selectRaw('spells.*, spell_name_translations.translation as name,
                GROUP_CONCAT(DISTINCT dungeon_translations.translation ORDER BY dungeon_translations.translation SEPARATOR ", ") AS dungeon_names')
            ->with(['npcs'])
            ->leftJoin('spell_dungeons', 'spell_dungeons.spell_id', '=', 'spells.id')
            ->leftJoin('dungeons', 'spell_dungeons.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations as dungeon_translations', static function (JoinClause $clause) {
                $clause->on('dungeon_translations.key', '=', 'dungeons.name')
                    ->where('dungeon_translations.locale', '=', 'en_US');
            })
            ->leftJoin('translations as spell_name_translations', static function (JoinClause $clause) {
                $clause->on('spell_name_translations.key', '=', 'spells.name')
                    ->where('spell_name_translations.locale', '=', 'en_US');
            })
            ->where('spells.hidden_on_map', false)
            ->groupBy('spells.id')
            ->orderBy('spell_name_translations.translation');

        if ($dungeon !== null) {
            $spells->where('spell_dungeons.dungeon_id', $dungeon->id);
        }

        $datatablesHandler = new SpellsDatatablesHandler($request);

        return $datatablesHandler->setBuilder($spells)
            ->addColumnHandler([
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }
}
