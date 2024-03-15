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
     * @return mixed
     *
     * @throws Exception
     */
    public function store(DungeonFormRequest $request, ?Dungeon $dungeon = null)
    {
        $validated = $request->validated();

        $validated['expansion_id']     = Expansion::where('shortname', Dungeon::findExpansionByKey($validated['key']))->firstOrFail()->id;
        $validated['active']           ??= 0;
        $validated['speedrun_enabled'] ??= 0;

        if ($dungeon === null) {
            $beforeDungeon = new Dungeon();
            $dungeon       = Dungeon::create($validated);
            $saveResult    = true;
        } else {
            $beforeDungeon = clone $dungeon;
            $saveResult    = $dungeon->update($validated);
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
    public function create(): View
    {
        $dungeons            = Dungeon::all()->keyBy('key');
        $availableKeysSelect = collect();
        foreach (Dungeon::ALL as $expansion => $dungeonKeys) {

            $availableKeysForExpansion = collect();
            foreach ($dungeonKeys as $dungeonKey) {
                if (!isset($dungeons[$dungeonKey])) {
                    $availableKeysForExpansion->put($dungeonKey, $dungeonKey);
                }
            }

            if ($availableKeysForExpansion->isNotEmpty()) {
                $availableKeysSelect->put(Expansion::ALL[$expansion], $availableKeysForExpansion);
            }
        }

        return view('admin.dungeon.edit', [
            'availableKeysSelect' => $availableKeysSelect,
        ]);
    }

    /**
     * @return Factory|View
     */
    public function edit(Request $request, Dungeon $dungeon): View
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'dungeon'    => $dungeon,
        ]);
    }

    /**
     * @return Factory|View
     *
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
     * @throws Exception
     */
    public function savenew(DungeonFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $dungeon = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.dungeon.flash.dungeon_created'));

        return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function get(): View
    {
        return view('admin.dungeon.list', [
            'models' => Dungeon::with(['mappingVersions'])
                ->select('dungeons.*')
                ->join('expansions', 'expansions.id', 'dungeons.expansion_id')
                ->orderByDesc('expansions.released_at')
                ->orderBy('dungeons.name')
                ->get(),
        ]);
    }
}
