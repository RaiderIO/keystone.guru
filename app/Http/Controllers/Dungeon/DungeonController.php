<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\DungeonFormRequest;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Service\Dungeon\DungeonServiceInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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

        $validated['expansion_id'] = Expansion::where(
            'shortname',
            Dungeon::findExpansionByKey($validated['key']),
        )->firstOrFail()->id;
        $validated['active'] ??= 0;
        $validated['raid'] ??= 0;
        $validated['heatmap_enabled'] ??= 0;
        $validated['speedrun_enabled'] ??= 0;

        $speedrunDifficulties = $validated['speedrun_difficulties'] ?? [];
        unset($validated['speedrun_difficulties']);

        $beforeDungeon = null;
        if ($dungeon === null) {
            $dungeon    = Dungeon::create($validated);
            $saveResult = true;
        } else {
            $beforeDungeon = clone $dungeon;
            $saveResult    = $dungeon->update($validated);
        }

        if ($saveResult) {
            $this->syncSpeedrunDifficulties($dungeon, $speedrunDifficulties);

            $this->mappingChanged($beforeDungeon, $dungeon);
        } else {
            abort(500, 'Unable to save dungeon');
        }

        return $dungeon;
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $dungeons            = Dungeon::all()->keyBy('key');
        $availableKeysSelect = collect();
        foreach (array_merge_recursive(Dungeon::ALL, Dungeon::ALL_RAID) as $expansion => $dungeonKeys) {
            $availableKeysForExpansion = collect();
            foreach ($dungeonKeys as $dungeonKey) {
                if (!isset($dungeons[$dungeonKey])) {
                    $availableKeysForExpansion->put($dungeonKey, $dungeonKey);
                }
            }

            if ($availableKeysForExpansion->isNotEmpty()) {
                $availableKeysSelect->put(__(sprintf('expansions.%s.name', $expansion)), $availableKeysForExpansion);
            }
        }

        return view('admin.dungeon.edit', [
            'availableKeysSelect' => $availableKeysSelect,
        ]);
    }

    /**
     * @return View
     */
    public function edit(Request $request, Dungeon $dungeon): View
    {
        return view('admin.dungeon.edit', [
            'expansions' => Expansion::all()->pluck('name', 'id'),
            'dungeon'    => $dungeon,
        ]);
    }

    /**
     * @return View
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
     * @return View
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

    public function changeContext(
        Request                 $request,
        Dungeon                 $dungeon,
        DungeonServiceInterface $dungeonService,
    ): RedirectResponse {
        $previousGameVersion = $dungeonService->getDungeonContext(Auth::user());
        $dungeonService->setDungeonContext($dungeon, Auth::user());

//        // If the referer page's route contains "dungeonroutes" we redirect to the "dungeonroutes" route instead
//        $referer = $request->headers->get('referer');
//        if ($referer) {
//            if (str_contains($referer, sprintf('/routes/%s', $previousGameVersion->key))) {
//                return redirect()->route('dungeonroutes.current');
//            } elseif (str_contains($referer, sprintf('/explore/%s', $previousGameVersion->key))) {
//                return redirect()->route('dungeon.explore.list');
//            }
//        }

        return Redirect::back();
    }

    /**
     * Replaces the dungeon's enabled speedrun difficulties with the given list.
     *
     * @param list<int> $difficulties
     */
    private function syncSpeedrunDifficulties(Dungeon $dungeon, array $difficulties): void
    {
        $dungeon->dungeonSpeedrunDifficulties()->delete();

        foreach (array_unique($difficulties) as $difficulty) {
            $dungeon->dungeonSpeedrunDifficulties()->create(['difficulty' => $difficulty]);
        }
    }
}
