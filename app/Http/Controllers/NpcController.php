<?php

namespace App\Http\Controllers;

use App\Http\Requests\NpcFormRequest;
use App\Models\Npc;
use App\Models\NpcClassification;
use Illuminate\Http\Request;

class NpcController extends Controller
{

    /**
     * @param NpcFormRequest $request
     * @param Npc $npc
     * @return array|mixed
     * @throws \Exception
     */
    public function store(NpcFormRequest $request, Npc $npc = null)
    {
        if ($npc === null) {
            $npc = new Npc();
        }

        $npc->classification_id = $request->get('classification_id');
        $npc->game_id = $request->get('game_id');
        $npc->name = $request->get('name');
        $npc->base_health = $request->get('base_health');

        if (!$npc->save()) {
            abort(500, 'Unable to save npc!');
        }

        return $npc;
    }

    /**
     * Show a page for creating a new expansion.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('admin.npc.edit', [
            'classifications' => NpcClassification::all()->pluck('name', 'id'),
            'headerTitle' => __('New npc')
        ]);
    }

    /**
     * @param Request $request
     * @param Npc $npc
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Npc $npc)
    {
        return view('admin.npc.edit', [
            'model' => $npc,
            'classifications' => NpcClassification::all()->pluck('name', 'id'),
            'headerTitle' => __('Edit npc')
        ]);
    }

    /**
     * Override to give the type hint which is required.
     *
     * @param NpcFormRequest $request
     * @param Npc $npc
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(NpcFormRequest $request, Npc $npc)
    {
        // Store it and show the edit page again
        $npc = $this->store($request, $npc);

        // Message to the user
        \Session::flash('status', __('Npc updated'));

        // Display the edit page
        return $this->edit($request, $npc);
    }

    /**
     * @param NpcFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(NpcFormRequest $request)
    {
        // Store it and show the edit page
        $npc = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Npc created'));

        return redirect()->route('admin.npc.edit', ["npc" => $npc]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.npc.list', ['models' => Npc::all()]);
    }
}
