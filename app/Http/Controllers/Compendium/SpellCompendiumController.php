<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compendium\SpellCompendiumRequest;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Spell\NameColumnHandler;
use App\Logic\Datatables\SpellsDatatablesHandler;
use App\Models\Spell\Spell;
use App\Service\Compendium\SpellCompendiumServiceInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\View\View;

class SpellCompendiumController extends Controller
{
    public function index(): View
    {
        return view('compendium.spell.index');
    }

    public function show(Spell $spell, SpellCompendiumServiceInterface $spellCompendiumService): View
    {
        if ($spell->hidden_on_map) {
            abort(404);
        }

        $spell->load(['gameVersion', 'dungeons.expansion', 'characteristic']);

        $npcs = $spell->npcs()->with(['classification', 'dungeons'])->get();

        return view('compendium.spell.show', [
            'spell'     => $spell,
            'npcs'      => $npcs,
            'eventFeed' => $spellCompendiumService->buildEventFeed($spell),
        ]);
    }

    public function get(SpellCompendiumRequest $request): array
    {
        $dungeon = $request->dungeon();

        $spells = Spell::query()
            ->selectRaw('spells.*, spell_name_translations.translation as name,
                GROUP_CONCAT(DISTINCT dungeon_translations.translation ORDER BY dungeon_translations.translation SEPARATOR ", ") AS dungeon_names,
                GROUP_CONCAT(DISTINCT npc_name_translations.translation ORDER BY npc_name_translations.translation SEPARATOR ", ") AS npc_names')
            ->leftJoin('spell_dungeons', 'spell_dungeons.spell_id', '=', 'spells.id')
            ->leftJoin('dungeons', 'spell_dungeons.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations as dungeon_translations', static function (JoinClause $clause) {
                $clause->on('dungeon_translations.key', '=', 'dungeons.name')
                    ->where('dungeon_translations.locale', '=', 'en_US');
            })
            ->leftJoin('npc_spells', 'npc_spells.spell_id', '=', 'spells.id')
            ->leftJoin('npcs', 'npc_spells.npc_id', '=', 'npcs.id')
            ->leftJoin('translations as npc_name_translations', static function (JoinClause $clause) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', 'en_US');
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
