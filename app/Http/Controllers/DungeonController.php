<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;
use App\Models\Expansion;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class DungeonController extends Controller
{
    use ChangesMapping;

    /**
     * @param DungeonFormRequest $request
     * @param Dungeon|null $dungeon
     * @return mixed
     * @throws Exception
     */
    public function store(DungeonFormRequest $request, Dungeon $dungeon = null)
    {
        if ($dungeon === null) {
            $beforeDungeon = new Dungeon();
            $saveResult    = Dungeon::create($request->validated());
        } else {
            $beforeDungeon = clone $dungeon;
            $saveResult    = $dungeon->update($request->validated());
        }

        if ($saveResult) {
            $this->mappingChanged($beforeDungeon, $dungeon);
        } else {
            abort(500, 'Unable to save dungeon');
        }

        return $dungeon;
    }

    /**
     * @return Factory|View
     */
    public function new()
    {
        return view('admin.dungeon.edit', ['expansions' => Expansion::all()->pluck('name', 'id')]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @return Factory|View
     */
    public function edit(Request $request, Dungeon $dungeon)
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'dungeon'    => $dungeon,
        ]);
    }

    /**
     * @param DungeonFormRequest $request
     * @param Dungeon $dungeon
     * @return Factory|View
     * @throws Exception
     */
    public function update(DungeonFormRequest $request, Dungeon $dungeon)
    {
        // Store it and show the edit page again
        $dungeon = $this->store($request, $dungeon);

        // Message to the user
        Session::flash('status', __('controller.dungeon.flash.dungeon_updated'));

        // Display the edit page
        return $this->edit($request, $dungeon);
    }

    /**
     * @param DungeonFormRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(DungeonFormRequest $request)
    {
        // Store it and show the edit page
        $dungeon = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.dungeon.flash.dungeon_created'));

        return redirect()->route('admin.dungeon.edit', ["dungeon" => $dungeon]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('admin.dungeon.list', [
            'models' => Dungeon::select('dungeons.*')
                ->join('expansions', 'expansions.id', 'dungeons.expansion_id')
                ->orderByDesc('expansions.released_at')
                ->orderBy('dungeons.name')
                ->get()
        ]);
    }
}
