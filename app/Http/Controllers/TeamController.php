<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 12-4-2019
 * Time: 00:02
 */

namespace App\Http\Controllers;

use App\Http\Requests\Tag\TagFormRequest;
use App\Http\Requests\TeamFormRequest;
use App\Models\File;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;
use Teapot\StatusCode;

class TeamController extends Controller
{
    /**
     * @param TeamFormRequest $request
     * @param string $team
     * @return mixed
     * @throws Exception
     */
    public function store(TeamFormRequest $request, string $team = null)
    {
        $new = $team === null;

        if ($new) {
            $team = new Team();
            $team->name = $request->get('name');
            $team->public_key = Team::generateRandomPublicKey();
        } else {
            $team = Team::where('public_key', $team)->firstOrFail();
        }

        /** @var Team $team */
        $team->description = $request->get('description');
        $team->invite_code = Team::generateRandomPublicKey(12, 'invite_code');
        $team->icon_file_id = -1;

        $logo = $request->file('logo');

        // Update or insert it
        if ($team->save()) {
            // Save was successful, now do any file handling that may be necessary
            if ($logo !== null) {
                try {
                    // Delete the icon should it exist already
                    if ($team->iconfile !== null) {
                        $team->iconfile->delete();
                    }

                    $icon = File::saveFileToDB($logo, $team, 'uploads');

                    // Update the expansion to reflect the new file ID
                    $team->icon_file_id = $icon->id;
                    $team->save();
                } catch (Exception $ex) {
                    if ($new) {
                        // Roll back the saving of the expansion since something went wrong with the file.
                        $team->delete();
                    }
                    throw $ex;
                }
            }

            if ($new) {
                // If saving team + logo was successful, save our own user as its first member
                $team->addMember(Auth::user(), 'admin');
            }
        }

        return $team;
    }

    /**
     * @return Factory|View
     */
    public function new()
    {
        return view('team.new');
    }

    /**
     * @param Request $request
     * @param string $team
     * @return Application|ResponseFactory|RedirectResponse|Response
     * @throws AuthorizationException
     */
    public function edit(Request $request, string $team)
    {
        $teamModel = $this->_getModel($team);
        if (!($teamModel instanceof Team)) {
            return $teamModel;
        }

        $this->authorize('edit', $teamModel);

        return view('team.edit', ['team' => $teamModel]);
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function delete(Request $request, Team $team)
    {
        $this->authorize('delete', $team);

        try {
            $team->delete();
        } catch (Exception $ex) {
            abort(500);
        }

        return redirect()->route('team.list');
    }

    /**
     * @param TeamFormRequest $request
     * @param string $team
     * @return Team|Factory|Builder|Model|RedirectResponse|View|object
     * @throws Exception
     */
    public function update(TeamFormRequest $request, string $team)
    {
        $teamModel = $this->_getModel($team);
        if (!($teamModel instanceof Team)) {
            return $teamModel;
        }

        $this->authorize('edit', $teamModel);

        // Store it and show the edit page again
        $teamModel = $this->store($request, $team);

        // Message to the user
        Session::flash('status', __('Team updated'));

        // Display the edit page
        return $this->edit($request, $team);
    }

    /**
     * @param TeamFormRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(TeamFormRequest $request)
    {
        // Store it and show the edit page
        $team = $this->store($request);

        // Message to the user
        Session::flash('status', __('Team created'));

        return redirect()->route('team.edit', ['team' => $team]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        $user = Auth::user();
        return view('team.list', ['models' => $user->teams]);
    }

    /**
     * @param Request $request
     * @param string $invitecode
     * @return Factory|View
     */
    public function invite(Request $request, string $invitecode)
    {
        /** @var Team $team */
        $team = Team::where('invite_code', $invitecode)->first();
        $result = null;

        if ($team !== null) {
            if ($team->isCurrentUserMember()) {
                $result = view('team.invite', ['team' => $team, 'member' => true]);
            } else {
                $result = view('team.invite', ['team' => $team]);
            }
        } else {
            abort(StatusCode::NOT_FOUND, 'Unable to find a team associated with this invite code');
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param string $invitecode
     * @return Factory|View
     */
    public function inviteaccept(Request $request, string $invitecode)
    {
        /** @var Team $team */
        $team = Team::where('invite_code', $invitecode)->first();

        if ($team->isCurrentUserMember()) {
            $result = view('team.invite', ['team' => $team, 'member' => true]);
        } else {
            $team->addMember(Auth::getUser(), 'member');

            Session::flash('status', sprintf(__('Success! You are now a member of team %s.'), $team->name));
            $result = redirect()->route('team.edit', ['team' => $team]);
        }

        return $result;
    }

    /**
     * Creates a tag from the tag manager
     *
     * @param TagFormRequest $request
     * @return Application|Factory|View
     */
    public function createtag(TagFormRequest $request)
    {
        $error = [];

        $tagCategoryId = TagCategory::fromName(TagCategory::DUNGEON_ROUTE_TEAM)->id;

        if (!Tag::where('name', $request->get('tag_name_new'))
            ->where('user_id', Auth::id())
            ->where('tag_category_id', $tagCategoryId)
            ->exists()) {

            Tag::saveFromRequest($request, $tagCategoryId);

            Session::flash('status', __('Tag created successfully'));
        } else {
            $error = ['tag_name_new' => __('This tag already exists')];
        }

        return view('team.edit')->withErrors($error);
    }

    /**
     * @param string $team
     * @return Team|Builder|Model|RedirectResponse|object
     */
    private function _getModel(string $team)
    {
        $teamModel = Team::where('name', $team)->first();

        if ($teamModel !== null) {
            return redirect()->route('team.edit', ['team' => $teamModel->public_key]);
        }

        $teamModel = Team::where('public_key', $team)->first();

        if ($teamModel === null) {
            abort(StatusCode::NOT_FOUND);
        }

        return $teamModel;
    }
}
