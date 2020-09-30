<?php

namespace App\Http\Controllers;

use App\Http\Requests\FloorFormRequest;
use App\Logic\MapContext\MapContextDungeon;
use App\Models\Dungeon;
use App\Models\Floor;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

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
        $floor->default = $request->get('default', false);

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
     * @return Factory|View
     */
    public function new(Request $request)
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::findOrFail($request->get('dungeon'));

        return view('admin.floor.new', [
            'headerTitle' => __('New floor'),
            'dungeon'     => $dungeon
        ]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return Application|Factory|RedirectResponse|View
     */
    public function edit(Request $request, Dungeon $dungeon, Floor $floor)
    {
        if ($floor->dungeon->id === $dungeon->id) {
            $dungeon = $floor->dungeon->load('floors');

            return view('admin.floor.edit', [
                'headerTitle' => sprintf(__('%s - Edit floor'), $dungeon->name),
                'dungeon'     => $dungeon,
                'model'       => $floor,
            ]);
        } else {
            Session::flash('warning', sprintf('Floor %s is not a part of dungeon %s', $floor->name, $dungeon->name));
            return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
        }
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return Factory|View
     */
    public function mapping(Request $request, Dungeon $dungeon, Floor $floor)
    {
        $dungeon = $floor->dungeon->load('floors');

        return view('admin.floor.mapping', [
            'model'       => $floor,
            'headerTitle' => __('Edit floor'),
            'mapContext'  => (new MapContextDungeon($dungeon, $floor))->toArray(),
        ]);
    }

    /**
     * @param FloorFormRequest $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return Factory|View
     * @throws Exception
     */
    public function update(FloorFormRequest $request, Dungeon $dungeon, Floor $floor)
    {
        // Store it and show the edit page again
        $floor = $this->store($request, $floor);

        // Message to the user
        Session::flash('status', __('Floor updated'));

        // Display the edit page
        return $this->mapping($request, $dungeon, $floor);
    }

    /**
     * @param FloorFormRequest $request
     * @param Dungeon $dungeon
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(FloorFormRequest $request, Dungeon $dungeon)
    {
        // Store it and show the edit page
        $floor = $this->store($request);

        // Message to the user
        Session::flash('status', __('Floor created'));

        return redirect()->route('admin.floor.edit.mapping', [
            'dungeon' => $request->get('dungeon'),
            'floor'   => $floor
        ]);
    }
}
