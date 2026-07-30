<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeasonFormRequest;
use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Season;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;

class SeasonController extends Controller
{
    /**
     * @throws Exception
     */
    public function store(SeasonFormRequest $request, ?Season $season = null): Season
    {
        $validated = $request->validated();

        $validated['presets'] ??= 0;

        if ((int)$validated['seasonal_affix_id'] === -1) {
            $validated['seasonal_affix_id'] = null;
        }

        $dungeonIds = $validated['dungeon_ids'] ?? [];
        unset($validated['dungeon_ids']);

        if ($season === null) {
            $season = Season::create($validated);
        } else {
            $season->update($validated);
        }

        $season->syncDungeons($dungeonIds);

        return $season;
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.season.edit', [
            'expansions'          => $this->getExpansionsSelect(),
            'seasonalAffixSelect' => $this->getSeasonalAffixSelect(),
            'dungeonsSelect'      => $this->getDungeonsSelect(),
            'selectedDungeonIds'  => [],
        ]);
    }

    /**
     * @return View
     */
    public function edit(Request $request, Season $season): View
    {
        return view('admin.season.edit', [
            'expansions'          => $this->getExpansionsSelect(),
            'seasonalAffixSelect' => $this->getSeasonalAffixSelect(),
            'dungeonsSelect'      => $this->getDungeonsSelect(),
            'selectedDungeonIds'  => $season->seasonDungeons()->pluck('dungeon_id')->toArray(),
            'season'              => $season,
        ]);
    }

    /**
     * @return View
     *
     * @throws Exception
     */
    public function update(SeasonFormRequest $request, Season $season)
    {
        // Store it and show the edit page again
        $season = $this->store($request, $season);

        // Message to the user
        Session::flash('status', __('controller.season.flash.season_updated'));

        // Display the edit page
        return $this->edit($request, $season);
    }

    /**
     * @throws Exception
     */
    public function savenew(SeasonFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $season = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.season.flash.season_created'));

        return redirect()->route('admin.season.edit', ['season' => $season]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return View
     */
    public function get(): View
    {
        return view('admin.season.list', [
            'models' => Season::with(['expansion'])->orderByDesc('start')->get(),
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private function getExpansionsSelect(): Collection
    {
        return Expansion::all()->mapWithKeys(fn(Expansion $expansion) => [$expansion->id => __($expansion->name)]);
    }

    /**
     * @return Collection<int, string>
     */
    private function getDungeonsSelect(): Collection
    {
        return Dungeon::orderBy('name')->get()->mapWithKeys(fn(Dungeon $dungeon) => [$dungeon->id => __($dungeon->name)]);
    }

    /**
     * @return Collection<int, string>
     */
    private function getSeasonalAffixSelect(): Collection
    {
        return collect([-1 => __('view_admin.season.edit.seasonal_affix_id_none')])
            ->union(
                Affix::whereIn('key', Affix::SEASONAL_AFFIXES)
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn(Affix $affix) => [$affix->affix_id => __($affix->name)]),
            );
    }
}
