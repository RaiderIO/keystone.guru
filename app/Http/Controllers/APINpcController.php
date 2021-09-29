<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\Datatables\ColumnHandler\Npc\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Npc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APINpcController extends Controller
{
    use ChangesMapping;

    public function delete(Request $request)
    {
        try {
            /** @var Npc $npc */
            $npc = Npc::findOrFail($request->get('id'));

            if ($npc->delete()) {
                broadcast(new ModelDeletedEvent($npc->dungeon, Auth::user(), $npc));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($npc, null);

            $result = response()->noContent();
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @param $request
     * @return array|mixed
     * @throws \Exception
     */
    public function list(Request $request)
    {
        $npcs = Npc::with(['dungeon', 'type', 'classification'])
            ->selectRaw('npcs.*, COUNT(enemies.id) as enemy_count')
            ->leftJoin('dungeons', 'npcs.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('enemies', 'npcs.id', '=', 'enemies.npc_id')
            ->groupBy('npcs.id');

        $datatablesHandler = (new NpcsDatatablesHandler($request));
        return $datatablesHandler->setBuilder($npcs)->addColumnHandler([
            new IdColumnHandler($datatablesHandler),
            new NameColumnHandler($datatablesHandler),
            new DungeonColumnHandler($datatablesHandler),
        ])->applyRequestToBuilder()->getResult();
    }
}
