<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Npc\NpcDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\Datatables\ColumnHandler\Npc\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Npc\Npc;
use App\Models\User;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;

class AjaxNpcController extends Controller
{
    use ChangesMapping;

    public function delete(Request $request)
    {
        try {
            /** @var Npc $npc */
            $npc = Npc::findOrFail($request->get('id'));

            if ($npc->delete()) {
                /** @var User $user */
                $user = Auth::user();
                foreach ($npc->dungeons as $dungeon) {
                    broadcast(new NpcDeletedEvent($dungeon, $user, $npc));
                }
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($npc, null);

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function get(Request $request): array
    {
        $npcs = Npc::with(['type', 'classification', 'enemyForces'])
            ->selectRaw('npcs.*, npc_name_translations.translation as name, GROUP_CONCAT(DISTINCT translations.translation SEPARATOR ", ") AS dungeon_names, COUNT(enemies.id) as enemy_count')
            ->join('npc_dungeons', 'npcs.id', '=', 'npc_dungeons.npc_id')
            ->leftJoin('dungeons', 'npc_dungeons.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations', static function (JoinClause $clause) {
                $clause->on('translations.key', 'dungeons.name')
                    ->on('translations.locale', DB::raw('"en_US"'));
            })
            ->leftJoin('translations as npc_name_translations', function (JoinClause $clause) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', 'en_US');
            })
            ->leftJoin('enemies', 'npcs.id', '=', 'enemies.npc_id')
            ->groupBy('npcs.id');
        //            ->leftJoin('mapping_versions', 'mapping_versions.dungeon_id', 'dungeons.id')
        //            ->whereColumn('enemies.mapping_version_id', 'mapping_versions.id')
        //            ->groupBy('npcs.id', 'mapping_versions.dungeon_id')
        //            ->orderByDesc('mapping_versions.version');

        $datatablesHandler = (new NpcsDatatablesHandler($request));

        return $datatablesHandler->setBuilder($npcs)
            ->addColumnHandler([
                new IdColumnHandler($datatablesHandler),
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }
}
