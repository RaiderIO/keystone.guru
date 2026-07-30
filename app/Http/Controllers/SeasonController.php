<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeasonFormRequest;
use App\Models\Affix;
use App\Models\Expansion;
use App\Models\Season;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use ReflectionClass;
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
            // The id must be one of Season::ALL_SEASONS - explicit rather than auto-incrementing so
            // that a season's identity is a deliberate code change (see SeasonFormRequest::rules()),
            // matching the constants the rest of the codebase references by name (e.g.
            // Season::SEASON_TWW_S1). MySQL's LAST_INSERT_ID() is not updated by an explicit
            // AUTO_INCREMENT value on a bulk query-builder insert(), but a normal Eloquent save()
            // does correctly report it back - verified empirically before relying on this.
            $id = (int)$validated['id'];
            unset($validated['id']);

            $season     = new Season($validated);
            $season->id = $id;
            $season->save();
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
            'availableSeasonIds'  => $this->getAvailableSeasonIdsSelect(),
            'expansions'          => $this->getExpansionsSelect(),
            'seasonalAffixSelect' => $this->getSeasonalAffixSelect(),
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
            'models' => Season::with(['expansion'])->orderByDesc('id')->get(),
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

    /**
     * The season ids that are declared as a Season::SEASON_* constant but don't have a row yet -
     * the only ids a new season is allowed to take.
     *
     * @return Collection<int, string>
     */
    private function getAvailableSeasonIdsSelect(): Collection
    {
        $availableIds = Season::getAvailableIds();

        $constants = (new ReflectionClass(Season::class))->getConstants();

        return collect($constants)
            ->filter(fn($value, $name) => is_int($value) && str_starts_with($name, 'SEASON_') && in_array($value, $availableIds, true))
            ->flip()
            ->sortKeys();
    }
}
