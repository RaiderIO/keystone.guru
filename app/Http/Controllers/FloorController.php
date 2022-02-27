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
     * @param Dungeon $dungeon
     * @param Floor|null $floor
     * @return Floor
     * @throws Exception
     */
    public function store(Request $request, Dungeon $dungeon, Floor $floor = null)
    {
        $beforeFloor = $floor === null ? null : clone $floor;

        if ($floor === null) {
            $floor = new Floor();
            // May not be set when editing
            $floor->dungeon_id = $dungeon->id;
        }

        $floor->index          = $request->get('index');
        $floor->mdt_sub_level  = $request->get('mdt_sub_level');
        $floor->name           = $request->get('name');
        $floor->default        = $request->get('default', false);
        $defaultMinEnemySize   = config('keystoneguru.min_enemy_size_default');
        $floor->min_enemy_size = $request->get('min_enemy_size', $defaultMinEnemySize);
        $floor->min_enemy_size = empty($floor->min_enemy_size) ? null : $floor->min_enemy_size;

        $defaultMaxEnemySize   = config('keystoneguru.max_enemy_size_default');
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
                        'direction' => $direction,
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
     * @param Dungeon $dungeon
     * @return Factory|View
     */
    public function new(Request $request, Dungeon $dungeon)
    {
        return view('admin.floor.edit', [
            'dungeon' => $dungeon,
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
                'dungeon'        => $dungeon,
                'floor'          => $floor,
                'floorCouplings' => FloorCoupling::where('floor1_id', $floor->id)->get(),
            ]);
        } else {
            Session::flash('warning', sprintf(__('views/admin.floor.flash.invalid_floor_id'), __($floor->name), __($dungeon->name)));
            return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
        }
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param Floor $floor
     * @return Factory|View
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function mapping(Request $request, Dungeon $dungeon, Floor $floor)
    {
        $dungeon = $floor->dungeon->load('floors');

        return view('admin.floor.mapping', [
            'floor'      => $floor,
            'mapContext' => (new MapContextDungeon($dungeon, $floor))->getProperties(),
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
        $floor = $this->store($request, $dungeon, $floor);

        // Message to the user
        Session::flash('status', __('views/admin.floor.flash.floor_updated'));

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
        $floor = $this->store($request, $dungeon);

        // Message to the user
        Session::flash('status', __('views/admin.floor.flash.floor_created'));

        return redirect()->route('admin.floor.edit', [
            'dungeon' => $dungeon,
            'floor'   => $floor,
        ]);
    }
}
