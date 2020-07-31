<?php

namespace App\Http\Controllers;

use App\Logic\Datatables\ColumnHandler\Users\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Npc;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class APINpcController extends Controller
{

    public function delete(Request $request)
    {
        try {
            /** @var Npc $npc */
            $npc = Npc::findOrFail($request->get('id'));

            $npc->delete();
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
            ->select(['npcs.*'])
            ->leftJoin('dungeons', 'npcs.dungeon_id', '=', 'dungeons.id');

        $datatablesHandler = (new NpcsDatatablesHandler($request));
        return $datatablesHandler->setBuilder($npcs)->addColumnHandler([
            new IdColumnHandler($datatablesHandler),
            new NameColumnHandler($datatablesHandler),
            new DungeonColumnHandler($datatablesHandler)
        ])->applyRequestToBuilder()->getResult();
    }
}
