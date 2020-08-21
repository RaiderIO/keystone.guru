<?php

namespace App\Http\Controllers;

use App\Http\Requests\FloorFormRequest;
use App\Logic\MapContext\MapContextDungeon;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Npc;
use Illuminate\Http\Request;

class FloorController extends Controller
{

    /**
     * @param Request $request
     * @param Floor|null $floor
     * @return Floor
     */
    public function store(Request $request, Floor $floor = null)
    {
        if ($floor === null) {
            $floor = new Floor();
            // May not be set when editing
            $floor->dungeon_id = $request->get('dungeon');
        }

        $floor->index = $request->get('index');
        $floor->name = $request->get('name');

        // Update or insert it
        if (!$floor->save()) {
            abort(500, 'Unable to save floor');
        } else {
            // Remove all existing relationships
            $floor->directConnectedFloors()->detach($request->get('connectedfloors'));
            $floor->reverseConnectedFloors()->detach($request->get('connectedfloors'));

            // Create a new direct relationship
            $floor->directConnectedFloors()->sync($request->get('connectedfloors'));
        }

        return $floor;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new(Request $request)
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::findOrFail($request->get('dungeon'));

        return view('admin.floor.edit', [
            'headerTitle' => __('New floor'),
            'mapContext' => (new MapContextDungeon($dungeon, $dungeon->floors->first()))->toArray()
        ]);
    }

    /**
     * @param Request $request
     * @param Floor $floor
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Floor $floor)
    {
        $dungeon = $floor->dungeon->load('floors');

        return view('admin.floor.edit', [
            'model' => $floor,
            'headerTitle' => __('Edit floor'),
            'mapContext' => (new MapContextDungeon($dungeon, $floor))->toArray(),
        ]);
    }

    /**
     * @param FloorFormRequest $request
     * @param Floor $floor
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(FloorFormRequest $request, Floor $floor)
    {
        // Store it and show the edit page again
        $floor = $this->store($request, $floor);

        // Message to the user
        \Session::flash('status', __('Floor updated'));

        // Display the edit page
        return $this->edit($request, $floor);
    }

    /**
     * @param FloorFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(FloorFormRequest $request)
    {
        // Store it and show the edit page
        $floor = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Floor created'));

        return redirect()->route('admin.floor.edit', [
            'dungeon' => $request->get('dungeon'),
            'floor'   => $floor
        ]);
    }
}
