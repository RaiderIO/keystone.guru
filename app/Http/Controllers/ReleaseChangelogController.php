<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReleaseChangelogFormRequest;
use App\Models\ReleaseChangelog;
use Illuminate\Http\Request;

class ReleaseChangelogController extends Controller
{
    /**
     * @param ReleaseChangelogFormRequest $request
     * @param ReleaseChangelog $changelog
     * @return mixed
     * @throws \Exception
     */
    public function store(ReleaseChangelogFormRequest $request, ReleaseChangelog $changelog = null)
    {
        if ($new = ($changelog === null)) {
            $changelog = new ReleaseChangelog();
        }

        // Something went wrong with saving
        if (!$changelog->saveFromRequest($request, 'changelogs')) {
            abort(500, 'Unable to save changelog');
        }

        return $changelog;
    }

    /**
     * Show a page for creating a new changelog.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('admin.changelog.edit', ['headerTitle' => __('New changelog')]);
    }

    /**
     * @param Request $request
     * @param ReleaseChangelog $changelog
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, ReleaseChangelog $changelog)
    {
        return view('admin.changelog.edit', ['model' => $changelog, 'headerTitle' => __('Edit changelog')]);
    }

    /**
     * @param ReleaseChangelogFormRequest $request
     * @param ReleaseChangelog $changelog
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(ReleaseChangelogFormRequest $request, ReleaseChangelog $changelog)
    {
        // Store it and show the edit page again
        $changelog = $this->store($request, $changelog);

        // Message to the user
        \Session::flash('status', __('ReleaseChangelog updated'));

        // Display the edit page
        return $this->edit($request, $changelog);
    }

    /**
     * @param ReleaseChangelogFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(ReleaseChangelogFormRequest $request)
    {
        // Store it and show the edit page
        $changelog = $this->store($request);

        // Message to the user
        \Session::flash('status', __('ReleaseChangelog created'));

        return redirect()->route('admin.changelog.edit', ["changelog" => $changelog]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.changelog.list', ['models' => ReleaseChangelog::all()]);
    }
}
