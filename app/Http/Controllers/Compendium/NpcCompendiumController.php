<?php

namespace App\Http\Controllers\Compendium;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compendium\NpcCompendiumRequest;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use Illuminate\Database\Query\JoinClause;
use Illuminate\View\View;

class NpcCompendiumController extends Controller
{
    public function index(): View
    {
        return view('compendium.npc.index', [
            'contextDungeon' => Dungeon::getUserOrDefaultDungeon(),
        ]);
    }

    public function get(NpcCompendiumRequest $request): array
    {
        $mappingVersion = $request->dungeon()->getCurrentMappingVersion();

        $npcs = Npc::query()
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
}
