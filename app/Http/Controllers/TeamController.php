<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 12-4-2019
 * Time: 00:02
 */

namespace App\Http\Controllers;

use App\Http\Requests\TeamFormRequest;
use App\Models\File;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * @param TeamFormRequest $request
     * @param Team $team
     * @return mixed
     * @throws \Exception
     */
    public function store($request, Team $team = null)
    {
        $new = $team === null;
        if ($new) {
            $team = new Team();
        }

        /** @var Team $team */
        $team->name = $request->get('name');
        $team->description = $request->get('description');
        $team->icon_file_id = -1;

        $logo = $request->file('logo');

        // Update or insert it
        if ($team->save()) {
            // Save was successful, now do any file handling that may be necessary
            if ($logo !== null) {
                try {
                    // Delete the icon should it exist already
                    if( $team->iconfile !== null ) {
                        $team->iconfile->delete();
                    }

                    $icon = File::saveFileToDB($logo, $team, 'uploads');

                    // Update the expansion to reflect the new file ID
                    $team->icon_file_id = $icon->id;
                    $team->save();
                } catch (\Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $team->delete();
                    }
                    throw $ex;
                }
            }
        }

        return $team;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function new()
    {
        return view('team.edit');
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Request $request, Team $team)
    {
        return view('team.edit', ['model' => $team]);
    }

    /**
     * @param TeamFormRequest $request
     * @param Team $team
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function update(TeamFormRequest $request, Team $team)
    {
        // Store it and show the edit page again
        $team = $this->store($request, $team);

        // Message to the user
        \Session::flash('status', __('Team updated'));

        // Display the edit page
        return $this->edit($request, $team);
    }

    /**
     * @param TeamFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function savenew(TeamFormRequest $request)
    {
        // Store it and show the edit page
        $team = $this->store($request);

        // Message to the user
        \Session::flash('status', __('Team created'));

        return redirect()->route('team.edit', ["team" => $team]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('team.list', ['models' => Team::all()]);
    }
}
