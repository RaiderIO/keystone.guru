<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Http\Requests\NpcFormRequest;
use App\Models\Npc;
use App\Models\NpcClassification;
use Teapot\StatusCode\Http;

class NpcController extends BaseController
{
    public function __construct()
    {
        parent::__construct('npc', 'admin');
    }

    public function getNewHeaderTitle()
    {
        return __('New NPC');
    }

    public function getEditHeaderTitle()
    {
        return __('Edit NPC');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        // Override so we can set the classifications for the edit page
        $this->_setVariables(array(
            'classifications' => NpcClassification::all()->pluck('name', 'id'),
            // 'floors' => DB::table('floors')->where('dungeon_id', '=', $id)
        ));

        return parent::new();
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        // Override so we can set the classifications for the edit page
        $this->_setVariables(array(
            'classifications' => NpcClassification::all()->pluck('name', 'id'),
            // 'floors' => DB::table('floors')->where('dungeon_id', '=', $id)
        ));

        return parent::edit($id);
    }

    public function list(Request $request)
    {
        return Npc::all()/*with(['vertices'])->*/
        ->get(['id', 'name']);
    }

    /**
     * @param NPCFormRequest $request
     * @param int $id
     * @return array|mixed
     * @throws \Exception
     */
    public function store($request, int $id = -1)
    {
        /** @var Npc $npc */
        $npc = Npc::findOrNew($id);
        $edit = $id !== -1;

        $npc->classification_id = $request->get('classification');
        $npc->game_id = $request->get('game_id');
        $npc->name = $request->get('name');
        $npc->base_health = $request->get('base_health');

        if (!$npc->save()) {
            abort(500, 'Unable to save npc!');
        }
        \Session::flash('status', sprintf(__('Npc %s'), $edit ? __("updated") : __("saved")));

        return $npc->id;
    }

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

    /**
     * Override to give the type hint which is required.
     *
     * @param NpcFormRequest $request
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(NpcFormRequest $request, $id)
    {
        return parent::_update($request, $id);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param NpcFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(NpcFormRequest $request)
    {
        // Store it and show the edit page for the new item upon success
        return parent::_savenew($request);
    }
}
