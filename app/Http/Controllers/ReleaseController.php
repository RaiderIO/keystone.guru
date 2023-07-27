<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReleaseFormRequest;
use App\Models\Release;
use App\Models\ReleaseChangelog;
use App\Models\ReleaseChangelogCategory;
use App\Models\ReleaseChangelogChange;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Session;

class ReleaseController extends Controller
{
    /**
     * @param ReleaseFormRequest $request
     * @param Release|null       $release
     * @return mixed
     */
    public function store(ReleaseFormRequest $request, Release $release = null)
    {
        if ($new = ($release === null)) {
            $release   = new Release();
            $changelog = new ReleaseChangelog();
        } else {
            $changelog = $release->changelog;
        }

        // Update the changelog
        $changelog->description = $request->get('changelog_description');
        $changelog->save();


        // Update changes
        $tickets    = $request->get('tickets', []);
        $changes    = $request->get('changes', []);
        $categories = $request->get('categories', []);

        // Delete existing changes
        $changelog->changes()->delete();
        // Unset the relation so it's reloaded
        $changelog->unsetRelation('changes');

        $releaseChangelogChangesAttributes = [];
        for ($i = 0; $i < count($tickets); $i++) {
            // Only filled in rows, but tickets may be null
            if ((int)$categories[$i] !== -1 && strlen($categories[$i]) > 0 && strlen($changes[$i]) > 0) {
                $releaseChangelogChangesAttributes[] = [
                    'release_changelog_id'          => $changelog->id,
                    'release_changelog_category_id' => $categories[$i],
                    'ticket_id'                     => is_null($tickets[$i]) ? null : intval(str_replace('#', '', $tickets[$i])),
                    'change'                        => $changes[$i],
                ];
            }
        }
        ReleaseChangelogChange::insert($releaseChangelogChangesAttributes);

        $changelog->load('changes');


        $release->version   = $request->get('version');
        $release->title     = $request->get('title', '') ?? '';
        $release->silent    = $request->get('silent', 0);
        $release->spotlight = $request->get('spotlight', 0);

        // Match the changelog to the release
        $release->release_changelog_id = $changelog->id;

        if ($release->save()) {
            $changelog->release_id = $release->id;
            $changelog->save();

            $release->setRelation('changelog', $changelog);
            $changelog->setRelation('release', $release);

            try {
                Artisan::call(sprintf('make:githubreleaseticket %s', $release->version));
                Artisan::call(sprintf('make:githubreleasepullrequest %s', $release->version));
            } catch (Exception $exception) {
                Session::flash('status', sprintf(__('controller.release.flash.github_exception'), $exception->getMessage()));
            }
        } // Something went wrong with saving
        else {
            abort(500, __('controller.release.error.unable_to_save_release'));
        }

        return $release;
    }

    /**
     * Show a page for creating a new release.
     *
     * @return Factory|View
     */
    public function new()
    {
        return view('admin.release.edit', [
            'categories' => ReleaseChangelogCategory::all(),
        ]);
    }

    /**
     * @param Request $request
     * @param Release $release
     * @return Factory|View
     */
    public function edit(Request $request, Release $release)
    {
        return view('admin.release.edit', [
            'release'    => $release,
            'categories' => ReleaseChangelogCategory::all(),
        ]);
    }

    /**
     * @param ReleaseFormRequest $request
     * @param Release            $release
     * @return Factory|View
     * @throws Exception
     */
    public function update(ReleaseFormRequest $request, Release $release)
    {
        // Store it and show the edit page again
        $release = $this->store($request, $release);

        // Message to the user
        Session::flash('status', __('controller.release.flash.release_updated'));

        // Display the edit page
        return $this->edit($request, $release);
    }

    /**
     * @param ReleaseFormRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(ReleaseFormRequest $request)
    {
        // Store it and show the edit page
        $release = $this->store($request);

        // Message to the user
        Session::flash('status', __('controller.release.flash.release_created'));

        return redirect()->route('admin.release.edit', ['release' => $release]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory
     */
    public function list()
    {
        return view('admin.release.list', ['models' => Release::orderBy('id', 'desc')->get()]);
    }

    /**
     * @param Release $release
     * @return Factory|View
     */
    public function view(Release $release)
    {
        return view('release.view', ['release' => $release]);
    }
}
