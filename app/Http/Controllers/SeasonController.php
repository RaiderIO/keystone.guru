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
        // Checkboxes don't submit when unchecked, so an absent 'active' means "unchecked" and
        // must persist as false, matching Dungeon::active's convention.
        $validated['active'] ??= 0;

        if ((int)$validated['seasonal_affix_id'] === -1) {
            $validated['seasonal_affix_id'] = null;
        }

        $dungeonIds = $validated['dungeon_ids'] ?? [];
        unset($validated['dungeon_ids']);

        if ($season === null) {
            // Deliberately not Season::create($validated): 'id' is intentionally absent from
            // Season::$fillable, so mass-assigning it would be silently dropped (this app never
            // calls preventSilentlyDiscardingAttributeFill()) and the row would get whatever id
            // MySQL's AUTO_INCREMENT hands out next, instead of the intended
            // Season::SEASON_TWW_S1-style constant that the rest of the codebase references by
            // name (see SeasonFormRequest::rules(), which restricts 'id' to Season::ALL_SEASONS).
            // Making 'id' fillable to allow create() would reopen the same primary-key-reassignment
            // hole fixed for Affix in 21ba580f5: SeasonFormRequest only requires 'id' on create, it
            // doesn't forbid it on update, so a PATCH carrying an 'id' would mass-assign a new
            // primary key onto an existing season. Instead we strip 'id' from the mass-assigned
            // data and set it directly on the model, bypassing the fillable guard for just this one
            // explicitly-validated case.
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
