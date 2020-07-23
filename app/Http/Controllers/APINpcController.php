<?php

namespace App\Http\Controllers;

use App\Logic\Datatables\NpcsDatatablesHandler;
use Illuminate\Http\Request;
use App\Models\Npc;
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
        $npcs = Npc::with(['dungeon', 'type', 'classification']);

        return (new NpcsDatatablesHandler($request))->setBuilder($npcs)->applyRequestToBuilder()->getResult();
    }
}
