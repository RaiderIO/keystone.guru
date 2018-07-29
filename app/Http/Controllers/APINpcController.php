<?php

namespace App\Http\Controllers;

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
            $result = ['result' => 'success'];
        } catch (\Exception $ex) {
            $result = response('Not found', Http::NOT_FOUND);
        }

        return $result;
    }

    public function list(Request $request)
    {
        return Npc::all()/*with(['vertices'])->*/
        ->get(['id', 'name']);
    }
}
