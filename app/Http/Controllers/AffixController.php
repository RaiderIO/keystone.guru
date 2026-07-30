<?php

namespace App\Http\Controllers;

use App\Http\Requests\AffixFormRequest;
use App\Models\Affix;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;

class AffixController extends Controller
{
    /**
     * @throws Exception
     */
    public function store(AffixFormRequest $request, ?Affix $affix = null): Affix
    {
        $validated = $request->validated();

        if ($affix === null) {
            // No icon is uploaded through the admin panel - mapping:save does not export icon_file_id,
            // and the real seeder always creates its own File row pointing at a static images/affixes/*.jpg asset.
            $validated['icon_file_id'] = -1;
            // The id must be one of Affix::ALL's values - explicit rather than auto-incrementing so
            // that a new affix's identity is a deliberate code change, matching how the rest of the
            // codebase references affixes by their Affix::AFFIX_* constant (see AffixFormRequest).
            $affix = Affix::create($validated);
        } else {
            // id is only settable on create (see AffixFormRequest::rules()) - strip it defensively
            // so an update request can never reassign an existing affix's primary key.
            unset($validated['id']);
            $affix->update($validated);
        }

        return $affix;
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.affix.edit', [
            'availableAffixIds' => $this->getAvailableAffixIdsSelect(),
        ]);
    }

    /**
     * @return View
     */
    public function edit(Request $request, Affix $affix): View
    {
        return view('admin.affix.edit', ['affix' => $affix]);
    }

    /**
     * @return View
     *
     * @throws Exception
     */
    public function update(AffixFormRequest $request, Affix $affix)
    {
        // Store it and show the edit page again
        $affix = $this->store($request, $affix);

        // Message to the user
        Session::flash('status', __('controller.affix.flash.affix_updated'));

        // Display the edit page
        return $this->edit($request, $affix);
    }

    /**
     * @throws Exception
     */
    public function savenew(AffixFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $affix = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.affix.flash.affix_created'));

        return redirect()->route('admin.affix.edit', ['affix' => $affix]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return View
     */
    public function get(): View
    {
        return view('admin.affix.list', ['models' => Affix::orderBy('key')->get()]);
    }

    /**
     * The affix ids declared in Affix::ALL that don't have a row yet - the only ids a new affix is
     * allowed to take.
     *
     * @return Collection<int, string>
     */
    private function getAvailableAffixIdsSelect(): Collection
    {
        $availableIds = Affix::getAvailableIds();

        return collect(Affix::ALL)
            ->filter(fn(int $id) => in_array($id, $availableIds, true))
            ->flip()
            ->sortKeys();
    }
}
