<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\SpellFormRequest;
use App\Models\Spell\Spell;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class SpellController extends Controller
{
    use ChangesMapping;

    /**
     * Checks if the incoming request is a save as new request or not.
     */
    private function isSaveAsNew(Request $request): bool
    {
        return $request->get('submit', 'submit') !== 'Submit';
    }

    /**
     * @return array|mixed
     *
     * @throws Exception
     */
    public function store(SpellFormRequest $request, ?Spell $spell = null)
    {
        // If we're saving as new, make a new Spell and save that instead
        if ($spell === null || $this->isSaveAsNew($request)) {
            $spell = new Spell();
        }

        $validated = $request->validated();

        //        else {
        //            $oldId = $spell->id;
        //        }

        $spellBefore = clone $spell;

        $spell->id             = $validated['id'];
        $spell->category       = $validated['category'];
        $spell->dispel_type    = $validated['dispel_type'];
        $spell->cooldown_group = $validated['cooldown_group'];
        $spell->icon_name      = $validated['icon_name'];
        $spell->name           = $validated['name'];
        $schools               = $validated['schools'] ?? [];
        $mask                  = 0;
        foreach ($schools as $school) {
            $mask |= (int)$school;
        }

        $spell->schools_mask = $mask;
        $spell->aura         = $validated['aura'] ?? false;
        $spell->selectable   = $validated['selectable'] ?? false;

        if ($spell->save()) {
            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($spellBefore, $spell);
        } // We gotta update any existing enemies with the old ID to the new ID, makes it easier to convert ids
        else {
            abort(500, 'Unable to save spell!');
        }

        return $spell;
    }

    /**
     * Show a page for creating a new spell.
     *
     * @return Factory|View
     */
    public function create(): View
    {
        return view('admin.spell.edit', $this->getEditViewParams());
    }

    /**
     * @return Factory|View
     */
    public function edit(Request $request, Spell $spell): View
    {
        return view('admin.spell.edit', array_merge($this->getEditViewParams(), [
            'spell' => $spell,
        ]));
    }

    /**
     * Override to give the type hint which is required.
     *
     * @return Factory|RedirectResponse|View
     *
     * @throws Exception
     */
    public function update(SpellFormRequest $request, Spell $spell)
    {
        if ($this->isSaveAsNew($request)) {
            return $this->savenew($request);
        } else {
            // Store it and show the edit page again
            $spell = $this->store($request, $spell);

            // Message to the user
            Session::flash('status', __('controller.spell.flash.spell_updated'));

            // Display the edit page
            return $this->edit($request, $spell);
        }
    }

    /**
     * @throws Exception
     */
    public function savenew(SpellFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $spell = $this->store($request);

        // Message to the user
        Session::flash('status', sprintf(__('controller.spell.flash.spell_created'), $spell->name));

        return redirect()->route('admin.spell.edit', ['spell' => $spell->id]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function get(): View
    {
        return view('admin.spell.list', ['models' => Spell::all()]);
    }

    private function getEditViewParams(): array
    {
        return [
            'categories'     => collect(Spell::ALL_CATEGORIES)->mapWithKeys(function (string $category) {
                return [
                    $category =>
                        __(sprintf('spells.category.%s', $category)),
                ];
            })->toArray(),
            'dispelTypes'    => Spell::ALL_DISPEL_TYPES,
            'schools'        => Spell::ALL_SCHOOLS,
            'cooldownGroups' => collect(Spell::ALL_COOLDOWN_GROUPS)->mapWithKeys(function (string $cooldownGroupKey) {
                return [
                    $cooldownGroupKey =>
                        __(sprintf('spells.cooldown_group.%s', $cooldownGroupKey)),
                ];
            })->toArray(),
        ];
    }
}
