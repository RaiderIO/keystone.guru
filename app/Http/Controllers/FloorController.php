<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\FloorFormRequest;
use App\Logic\MapContext\MapContextDungeon;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\FloorCoupling;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class FloorController extends Controller
{
    use ChangesMapping;

    /**
     * @param Request $request
     * @param Floor|null $floor
     * @return Floor
     */
    public function store(Request $request, Floor $floor = null)
    {
        $beforeFloor = clone $floor;

        if ($floor === null) {
            $floor = new Floor();
            // May not be set when editing
            $floor->dungeon_id = $request->get('dungeon');
        }

        $floor->index = $request->get('index');
        $floor->name = $request->get('name');
        $floor->default = $request->get('default', false);
        $defaultMinEnemySize = config('keystoneguru.min_enemy_size_default');
        $floor->min_enemy_size = $request->get('min_enemy_size', $defaultMinEnemySize);
        $floor->min_enemy_size = empty($floor->min_enemy_size) ? null : $floor->min_enemy_size;

        $defaultMaxEnemySize = config('keystoneguru.max_enemy_size_default');
        $floor->max_enemy_size = $request->get('max_enemy_size', $defaultMaxEnemySize);
        $floor->max_enemy_size = empty($floor->max_enemy_size) ? null : $floor->max_enemy_size;

        // Update or insert it
        if ($floor->save()) {
            // Delete all directly connected floors
            $floor->floorcouplings()->delete();

            foreach ($floor->dungeon->floors as $connectedFloorCandidate) {
                $isConnected = $request->get(sprintf('floor_%s_connected', $connectedFloorCandidate->id), false);

                if ($isConnected) {
                    $direction = $request->get(sprintf('floor_%s_direction', $connectedFloorCandidate->id));

                    // Recreate one by one
                    FloorCoupling::insert([
                        'floor1_id' => $floor->id,
                        'floor2_id' => $connectedFloorCandidate->id,
                        'direction' => $direction
                    ]);
                }
            }

            $this->mappingChanged($beforeFloor, $floor);
        } else {
            abort(500, 'Unable to save floor');
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
                'headerTitle'    => sprintf(__('%s - Edit floor'), $dungeon->name),
                'dungeon'        => $dungeon,
                'model'          => $floor,
                'floorCouplings' => FloorCoupling::where('floor1_id', $floor->id)->get()
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
        return $this->edit($request, $dungeon, $floor);
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
