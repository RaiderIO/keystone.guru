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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                broadcast(new NpcDeletedEvent($npc->dungeon, $user, $npc));
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
        $npcs = Npc::with(['dungeon', 'type', 'classification', 'enemyForces'])
            ->selectRaw('npcs.*, COUNT(enemies.id) as enemy_count')
            ->leftJoin('dungeons', 'npcs.dungeon_id', '=', 'dungeons.id')
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
